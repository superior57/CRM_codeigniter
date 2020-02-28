<?php

defined('BASEPATH') or exit('No direct script access allowed');

function access_control_media($attr, $path, $data, $volume, $isDir, $relpath)
{
    $paths = explode('/', $path);
    $basename = end($paths);

    if ($basename == 'index.html') {
        return true;
    }

    return $basename[0] === '.'                  // if file/folder begins with '.' (dot)
                     && strlen($relpath) !== 1           // but with out volume root
                ? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
                :  null;                                 // else elFinder decide it itself
}

/**
 * Copy directory and all contents
 * @since  Version 1.0.2
 * @param  string  $source      string
 * @param  string  $dest        destination
 * @param  integer $permissions folder permissions
 * @return boolean
 */
function xcopy($source, $dest, $permissions = 0755)
{
    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }
    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }
    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }
        // Deep copy directories
        xcopy("$source/$entry", "$dest/$entry", $permissions);
    }
    // Clean up
    $dir->close();

    return true;
}
/**
 * Delete directory
 * @param  string $dirPath dir
 * @return boolean
 */
function delete_dir($dirPath)
{
    if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            delete_dir($file);
        } else {
            unlink($file);
        }
    }
    if (rmdir($dirPath)) {
        return true;
    }

    return false;
}
/**
 * Is file image
 * @param  string  $path file path
 * @return boolean
 */
function is_image($path)
{
    $possibleBigFiles = [
            'pdf', 'zip', 'mp4', 'ai',
            'psd', 'ppt',  'gzip', 'rar',
            'tar', 'tgz',  'mpeg', 'mpg',
            'flv', 'mov',  'wav', 'avi',
            'dwg'
        ];

    $pathArray = explode('.', $path);
    $ext       = end($pathArray);
    // Causing performance issues if the file is too big
    if (in_array($ext, $possibleBigFiles)) {
        return false;
    }

    $image      = @getimagesize($path);
    $image_type = $image[2];
    if (in_array($image_type, [
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_BMP,
    ])) {
        return true;
    }

    return false;
}
/**
 * Supported html5 video extensions
 * @return array
 */
function get_html5_video_extensions()
{
    return hooks()->apply_filters(
        'html5_video_extensions',
        [
            'mp4',
            'm4v',
            'webm',
            'ogv',
            'ogg',
            'flv',
        ]
    );
}
/**
 * Check if filename/path is video file
 * @param  string  $path
 * @return boolean
 */
function is_html5_video($path)
{
    $ext = get_file_extension($path);
    if (in_array($ext, get_html5_video_extensions())) {
        return true;
    }

    return false;
}
/**
 * Get file extension by filename
 * @param  string $file_name file name
 * @return mixed
 */
function get_file_extension($file_name)
{
    return substr(strrchr($file_name, '.'), 1);
}

/**
 * Unique filename based on folder
 * @since  Version 1.0.1
 * @param  string $dir      directory to compare
 * @param  string $filename filename
 * @return string           the unique filename
 */
function unique_filename($dir, $filename)
{
    // Separate the filename into a name and extension.
    $info     = pathinfo($filename);
    $ext      = !empty($info['extension']) ? '.' . $info['extension'] : '';
    $filename = sanitize_file_name($filename);
    $number   = '';
    // Change '.ext' to lower case.
    if ($ext && strtolower($ext) != $ext) {
        $ext2      = strtolower($ext);
        $filename2 = preg_replace('|' . preg_quote($ext) . '$|', $ext2, $filename);
        // Check for both lower and upper case extension or image sub-sizes may be overwritten.
        while (file_exists($dir . "/$filename") || file_exists($dir . "/$filename2")) {
            $filename = str_replace([
                "-$number$ext",
                "$number$ext",
            ], "-$new_number$ext", $filename);
            $filename2 = str_replace([
                "-$number$ext2",
                "$number$ext2",
            ], "-$new_number$ext2", $filename2);
            $number = $new_number;
        }

        return $filename2;
    }
    while (file_exists($dir . "/$filename")) {
        if ('' == "$number$ext") {
            $filename = "$filename-" . ++$number;
        } else {
            $filename = str_replace([
                "-$number$ext",
                "$number$ext",
            ], '-' . ++$number . $ext, $filename);
        }
    }

    return $filename;
}
/**
 * Sanitize file name
 * @param  string $filename filename
 * @return mixed
 */
function sanitize_file_name($filename)
{
    $CI       = & get_instance();
    $filename = $CI->security->sanitize_filename($filename);

    return $filename;
}
/**
 * Get mime class by mime - admin system function
 * @param  string $mime file mime type
 * @return string
 */
function get_mime_class($mime)
{
    if (empty($mime) || is_null($mime)) {
        return 'mime mime-file';
    }
    $_temp_mime = explode('/', $mime);
    $part1      = $_temp_mime[0];
    $part2      = $_temp_mime[1];
    // Image
    if ($part1 == 'image') {
        if (strpos($part2, 'photoshop') !== false) {
            return 'mime mime-photoshop';
        }
        ;

        return 'mime mime-image';
    }
    // Audio
    elseif ($part1 == 'audio') {
        return 'mime mime-audio';
    }
    // Video
    elseif ($part1 == 'video') {
        return 'mime mime-video';
    }
    // Text
    elseif ($part1 == 'text') {
        return 'mime mime-file';
    }
    // Applications
    elseif ($part1 == 'application') {
        // Pdf
        if ($part2 == 'pdf') {
            return 'mime mime-pdf';
        }
        // Ilustrator
        elseif ($part2 == 'illustrator') {
            return 'mime mime-illustrator';
        }
        // Zip
        elseif ($part2 == 'zip' || $part2 == 'gzip' || strpos($part2, 'tar') !== false || strpos($part2, 'compressed') !== false) {
            return 'mime mime-zip';
        }
        // PowerPoint
        elseif (strpos($part2, 'powerpoint') !== false || strpos($part2, 'presentation') !== false) {
            return 'mime mime-powerpoint ';
        }
        // Excel
        elseif (strpos($part2, 'excel') !== false || strpos($part2, 'sheet') !== false) {
            return 'mime mime-excel';
        }
        // Word
        elseif ($part2 == 'msword' || $part2 == 'rtf' || strpos($part2, 'document') !== false) {
            return 'mime mime-word';
        }
        // Else

        return 'mime mime-file';
    }
    // Else

    return 'mime mime-file';
}

/**
 * Convert bytes of files to readable seize
 * @param  string $path file path
 * @param  string $filesize file path
 * @return mixed
 */
function bytesToSize($path, $filesize = '')
{
    if (!is_numeric($filesize)) {
        $bytes = sprintf('%u', filesize($path));
    } else {
        $bytes = $filesize;
    }
    if ($bytes > 0) {
        $unit  = intval(log($bytes, 1024));
        $units = [
            'B',
            'KB',
            'MB',
            'GB',
        ];
        if (array_key_exists($unit, $units) === true) {
            return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
        }
    }

    return $bytes;
}
/**
 * List folder on a specific path
 * @param  stirng $path
 * @return array
 */
function list_folders($path)
{
    $folders = [];
    foreach (new DirectoryIterator($path) as $file) {
        if ($file->isDot()) {
            continue;
        }
        if ($file->isDir()) {
            array_push($folders, $file->getFilename());
        }
    }

    return $folders;
}
/**
 * List files in a specific folder
 * @param  string $dir directory to list files
 * @return array
 */
function list_files($dir)
{
    $ignored = [
        '.',
        '..',
        '.svn',
        '.htaccess',
        'index.html',
    ];
    $files = [];
    foreach (scandir($dir) as $file) {
        if (in_array($file, $ignored)) {
            continue;
        }
        $files[$file] = filectime($dir . '/' . $file);
    }
    arsort($files);
    $files = array_keys($files);

    return ($files) ? $files : [];
}
// Returns a file size limit in bytes based on the PHP upload_max_filesize
// and post_max_size
function file_upload_max_size()
{
    static $max_size = -1;

    if ($max_size < 0) {
        // Start with post_max_size.
        $max_size = parse_upload_size(ini_get('post_max_size'));

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = parse_upload_size(ini_get('upload_max_filesize'));
        // if post max upload size is 0 then its unlimited, use upload_max_size instead
        if ($max_size == 0) {
            $max_size = $upload_max;
        } else {
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
    }

    return $max_size;
}

/**
 * Function that will read file and will replace string
 * @param  string $path    path to file
 * @param  string $find    find string
 * @param  string $replace replace string
 * @return boolean
 */
function replace_in_file($path, $find, $replace)
{
    $CI = & get_instance();
    $CI->load->helper('file');
    @chmod($path, FILE_WRITE_MODE);
    $file = read_file($path);
    $file = trim($file);
    $file = str_replace($find, $replace, $file);
    $file = trim($file);
    if (!$fp = fopen($path, FOPEN_WRITE_CREATE_DESTRUCTIVE)) {
        return false;
    }
    flock($fp, LOCK_EX);
    fwrite($fp, $file, strlen($file));
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($path, FILE_READ_MODE);

    return true;
}
/**
 * Parsing the max_upload_size value
 * Function called from file_upload_max_size
 * @param  mixed $size
 * @return int
 */
function parse_upload_size($size)
{
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    if ($unit) {
        // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }

    return round($size);
}

/**
 * Used in to eq preview images where the files are protected with .htaccess
 * @param  string  $path    full path
 * @param  boolean $preview
 * @return string
 */
function protected_file_url_by_path($path, $preview = false)
{
    if ($preview) {
        $fname     = pathinfo($path, PATHINFO_FILENAME);
        $fext      = pathinfo($path, PATHINFO_EXTENSION);
        $thumbPath = pathinfo($path, PATHINFO_DIRNAME) . '/' . $fname . '_thumb.' . $fext;
        if (file_exists($thumbPath)) {
            return str_replace(FCPATH, '', $thumbPath);
        }

        return str_replace(FCPATH, '', $path);
    }

    return str_replace(FCPATH, '', $path);
}

/**
 * Custom project file url helper function
 * @param  array  $file    file from database
 * @param  boolean $preview is preview image, will return url with thumb
 * @return string
 */
function project_file_url($file, $preview = false)
{
    $path     = 'uploads/projects/' . $file['project_id'] . '/';
    $fullPath = FCPATH . $path . $file['file_name'];
    $url      = base_url($path . $file['file_name']);

    if (!empty($file['external']) && !empty($file['thumbnail_link'])) {
        $url = $file['thumbnail_link'];
    } else {
        if ($preview) {
            $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
            $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
            $thumbPath = pathinfo($fullPath, PATHINFO_DIRNAME) . '/' . $fname . '_thumb.' . $fext;
            if (file_exists($thumbPath)) {
                $url = base_url('uploads/projects/' . $file['project_id'] . '/' . $fname . '_thumb.' . $fext);
            }
        }
    }

    return $url;
}

/**
 * Check if filename/path is markdown file
 * @param  string  $path full path with filename
 * @return boolean
 */
function is_markdown_file($path)
{
    $extensions = ['.markdown', '.mdown', '.mkdn', '.md', '.mkd', '.mdwn', '.mdtxt', '.mdtext', '.text', '.Rmd'];
    $extensions = hooks()->apply_filters('markdown_extensions', $extensions);

    foreach ($extensions as $markdownExtension) {
        if (endsWith($path, $markdownExtension)) {
            return true;
        }
    }

    return false;
}
/**
 * Parse markdown preview
 * @param  string $path full markdown file path
 * @return mixed
 */
function markdown_parse_preview($path)
{
    $Parsedown = new Parsedown();

    $markDownSafeMode = hooks()->apply_filters('mark_down_safe_mode', 'true');
    $Parsedown->setSafeMode($markDownSafeMode == 'true' ? true : false);

    $contents = @file_get_contents($path);

    if (!$contents) {
        return false;
    }

    return $Parsedown->text($contents);
}

/**
 * Get all files/folder from dir in an array
 * @param  string $dir      directory full path
 * @param  array  &$results
 * @return array
 */
function get_dir_contents($dir, &$results = [])
{
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } elseif ($value != '.' && $value != '..') {
            get_dir_contents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

/**
 * @since  2.3.2
 * Get app TEMP_FOLDER constant and perform additional checks
 * This is the recommended function that should be used to get the TEMP_FOLDER constant
 * @return string
 */
function app_temp_dir()
{
    if (!is_dir(TEMP_FOLDER)) {
        mkdir(TEMP_FOLDER, DIR_WRITE_MODE);
    }

    // In case index.html is removed from the folder, re-create it again
    if (!file_exists(TEMP_FOLDER . '/index.html')) {
        fopen(TEMP_FOLDER . '/index.html', 'w');
    }

    return TEMP_FOLDER;
}

/**
 * Try to delete temporary files/folder in TEMP_FOLDER (TEMP_FOLDER is constant)
 * This function is called via cron job
 * @since  2.3.2
 * @return void
 */
function app_maybe_delete_old_temporary_files()
{
    $older_than = _delete_temporary_files_older_then();
    $files      = list_files(TEMP_FOLDER);
    foreach ($files as $file) {
        $path = TEMP_FOLDER . $file;

        if ($file == 'index.html') {
            continue;
        } elseif (strpos($file, '-upgrade-version-') !== false) {
            if ($data = get_last_upgrade_copy_data()) {
                $basename = basename($data->path);
                if ($basename == $file && (time() - $data->time) > $older_than) {
                    @unlink($data->path);
                    update_option('last_upgrade_copy_data', '');
                }
            } else {
                // Is probably an old upgrade file because of multiple upgrade tries
                // Delete it in all cases
                @unlink($path);
            }
        } else {
            if ((time() - filectime($path)) > $older_than) {
                @unlink($path);
            }
        }
    }

    // In case index.html is removed from the folder, re-create it again
    if (!file_exists(TEMP_FOLDER . '/index.html')) {
        fopen(TEMP_FOLDER . '/index.html', 'w');
    }

    $folders = list_folders(TEMP_FOLDER);
    foreach ($folders as $folder) {
        $path = TEMP_FOLDER . $folder;
        if ((time() - filectime($path)) > $older_than) {
            @delete_dir($path);
        }
    }
}

/**
 * Get the number of seconds to delete temporary old files
 * @since  2.3.2
 * @return integer
 */
function _delete_temporary_files_older_then()
{
    return hooks()->apply_filters('delete_old_temporary_files_older_than', 1800); // 30 minutes is default
}


if (!function_exists('validate_file')) {
    /**
     * Validate file name
     *
     * @since  2.3.2
     *
     * A return value of `1` means the file path contains directory traversal.
     *
     * A return value of `2` means the file path contains a Windows drive path.
     *
     * A return value of `3` means the file is not in the allowed files list.
     *
     * @param  string $file          file name to validate
     * @param  array  $allowed_files allowed files
     * @return integer
     */
    function validate_file($file, $allowed_files = [])
    {
        // `../` on its own is not allowed:
        if ('../' === $file) {
            return 1;
        }

        // More than one occurence of `../` is not allowed:
        if (preg_match_all('#\.\./#', $file, $matches, PREG_SET_ORDER) && (count($matches) > 1)) {
            return 1;
        }

        // `../` which does not occur at the end of the path is not allowed:
        if (false !== strpos($file, '../') && '../' !== mb_substr($file, -3, 3)) {
            return 1;
        }

        // Files not in the allowed file list are not allowed:
        if (! empty($allowed_files) && ! in_array($file, $allowed_files)) {
            return 3;
        }

        // Absolute Windows drive paths are not allowed:
        if (':' == substr($file, 1, 1)) {
            return 2;
        }

        return 0;
    }
}
