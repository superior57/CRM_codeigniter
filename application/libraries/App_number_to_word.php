<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_number_to_word
{
    // TODO
    // add to options
    // words without spaces
    // array of possible numbers => words
    private $word_array = [];

    // thousand array,
    private $thousand = [];

    // variables
    private $val;

    private $currency0;

    private $currency1;

    // codeigniter instance
    private $ci;

    private $val_array;

    private $dec_value;

    private $dec_word;

    private $num_value;

    private $num_word;

    private $val_word;

    private $original_val;

    private $language;

    public function __construct($params = [])
    {
        $l        = '';
        $this->ci = & get_instance();
        if (is_numeric($params['clientid'])) {
            $client_language = get_client_default_language($params['clientid']);
            if (!empty($client_language)) {
                if (file_exists(APPPATH . 'language/' . $client_language)) {
                    $l = $client_language;
                }
            }
        }

        $language = $l;
        if ($language == '') {
            $language = get_option('active_language');
        }

        $this->ci->lang->load($language . '_num_words_lang', $language);

        if (file_exists(APPPATH . 'language/' . $language . '/custom_lang.php')) {

            // Unset the previous custom_lang in case is already loaded before num_words_lang
            if (isset($this->ci->lang->is_loaded['custom_lang.php'])) {
                unset($this->ci->lang->is_loaded['custom_lang.php']);
            }

            $this->ci->lang->load('custom_lang', $language);
        }

        $this->language = $language;

        array_push($this->thousand, '');
        array_push($this->thousand, _l('num_word_thousand') . ' ');
        array_push($this->thousand, _l('num_word_million') . ' ');
        array_push($this->thousand, _l('num_word_billion') . ' ');
        array_push($this->thousand, _l('num_word_trillion') . ' ');
        array_push($this->thousand, _l('num_word_zillion') . ' ');
        for ($i = 1; $i < 100; $i++) {
            $this->word_array[$i] = _l('num_word_' . $i);
        }
        for ($i = 100; $i <= 900; $i = $i + 100) {
            $this->word_array[$i] = _l('num_word_' . $i);
        }
    }

    public function convert($in_val = 0, $in_currency0 = '', $in_currency1 = true)
    {
        $this->original_val = $in_val;
        $this->val          = $in_val;
        $this->currency0    = _l('num_word_' . mb_strtoupper($in_currency0, 'UTF-8'));

        if (strtolower($in_currency0) == 'inr') {
            $final_val = $this->convert_indian($in_val);
        } else {
            // Currency not found
            if (strpos($this->currency0, 'num_word_') !== false) {
                $this->currency0 = '';
            }
            if ($in_currency1 == false) {
                $this->currency1 = '';
            } else {
                $this->currency1 = _l('num_word_cents');
            }
            // remove commas from comma separated numbers
            $this->val = abs(floatval(str_replace(',', '', $this->val)));
            if ($this->val > 0) {
                // convert to number format
                $this->val = number_format($this->val, '2', ',', ',');
                // split to array of 3(s) digits and 2 digit
                $this->val_array = explode(',', $this->val);
                // separate decimal digit
                $this->dec_value = intval($this->val_array[count($this->val_array) - 1]);
                if ($this->dec_value > 0) {
                    $w_and = _l('number_word_and');
                    $w_and = ($w_and == ' ' ? '' : $w_and .= ' ');
                    // convert decimal part to word;
                    $this->dec_word = $w_and . '' . $this->word_array[$this->dec_value] . ' ' . $this->currency1;
                }
                // loop through all 3(s) digits in VAL array
                $t = 0;
                // initialize the number to word variable
                $this->num_word = '';

                for ($i = count($this->val_array) - 2; $i >= 0; $i--) {
                    // separate each element in VAL array to 1 and 2 digits
                    $this->num_value = intval($this->val_array[$i]);

                    // if VAL = 0 then no word
                    if ($this->num_value == 0) {
                        $this->num_word = ' ' . $this->num_word;
                    }

                    // if 0 < VAL < 100 or 2 digits
                    elseif (strlen($this->num_value . '') <= 2) {
                        $this->num_word = $this->word_array[$this->num_value] . ' ' . $this->thousand[$t] . $this->num_word;
                        // add 'and' if not last element in VAL
                        if ($i == 1) {
                            $w_and          = _l('number_word_and');
                            $w_and          = ($w_and == ' ' ? '' : $w_and .= ' ');
                            $this->num_word = $w_and . '' . $this->num_word;
                        }
                    }
                    // if VAL >= 100, set the hundred word
                    else {
                        @$this->num_word = $this->word_array[mb_substr($this->num_value, 0, 1) . '00'] . (intval(mb_substr($this->num_value, 1, 2)) > 0 ? (_l('number_word_and') != ' ' ? ' ' . _l('number_word_and') . ' ' : ' ') : '') . $this->word_array[intval(mb_substr($this->num_value, 1, 2))] . ' ' . $this->thousand[$t] . $this->num_word;
                    }
                    $t++;
                }
                // add currency to word
                if (!empty($this->num_word)) {
                    $this->num_word .= '' . $this->currency0;
                }
            }
            // join the number and decimal words
            $this->val_word = $this->num_word . ' ' . $this->dec_word;

            if (get_option('total_to_words_lowercase') == 1) {
                $final_val = trim(mb_strtolower($this->val_word, 'UTF-8'));
            } else {
                $final_val = trim($this->val_word);
            }
        }

        return hooks()->apply_filters('before_return_num_word', $final_val, [
            'original_number' => $this->original_val,
            'currency'        => $in_currency0,
            'language'        => $this->language,
        ]);
    }

    private function convert_indian($num)
    {
        $count = 0;
        global $ones, $tens, $triplets;
        $ones = [
    '',
    ' ' . _l('num_word_1'),
    ' ' . _l('num_word_2'),
    ' ' . _l('num_word_3'),
    ' ' . _l('num_word_4'),
    ' ' . _l('num_word_5'),
    ' ' . _l('num_word_6'),
    ' ' . _l('num_word_7'),
    ' ' . _l('num_word_8'),
    ' ' . _l('num_word_9'),
    ' ' . _l('num_word_10'),
    ' ' . _l('num_word_11'),
    ' ' . _l('num_word_12'),
    ' ' . _l('num_word_13'),
    ' ' . _l('num_word_14'),
    ' ' . _l('num_word_15'),
    ' ' . _l('num_word_16'),
    ' ' . _l('num_word_17'),
    ' ' . _l('num_word_18'),
    ' ' . _l('num_word_19'),
  ];
        $tens = [
    '',
    '',
    ' ' . _l('num_word_20'),
    ' ' . _l('num_word_30'),
    ' ' . _l('num_word_40'),
    ' ' . _l('num_word_50'),
    ' ' . _l('num_word_60'),
    ' ' . _l('num_word_70'),
    ' ' . _l('num_word_80'),
    ' ' . _l('num_word_90'),
  ];

        $triplets = [
    '',
    ' ' . _l('num_word_thousand'),
    ' ' . _l('num_word_million'),
    ' ' . _l('num_word_billion'),
    ' ' . _l('num_word_trillion'),
    ' Quadrillion',
    ' Quintillion',
    ' Sextillion',
    ' Septillion',
    ' Octillion',
    ' Nonillion',
  ];

        return $this->convert_number_indian($num);
    }

    /**
     * Function to dislay tens and ones
     */
    private function common_loop_indian($val, $str1 = '', $str2 = '')
    {
        global $ones, $tens;
        $string = '';
        if ($val == 0) {
            $string .= $ones[$val];
        } elseif ($val < 20) {
            $string .= $str1 . $ones[$val] . $str2;
        } else {
            $string .= $str1 . $tens[(int) ($val / 10)] . $ones[$val % 10] . $str2;
        }

        return $string;
    }

    /**
     * returns the number as an anglicized string
     */
    private function convert_number_indian($num)
    {
        $num = (int) $num;    // make sure it's an integer

        if ($num < 0) {
            return 'negative' . $this->convert_tri_indian(-$num, 0);
        }

        if ($num == 0) {
            return 'Zero';
        }

        return $this->convert_tri_indian($num, 0);
    }

    /**
     * recursive fn, converts numbers to words
     */
    private function convert_tri_indian($num, $tri)
    {
        global $ones, $tens, $triplets, $count;
        $test = $num;
        $count++;
        // chunk the number, ...rxyy
        // init the output string
        $str = '';
        // to display hundred & digits
        if ($count == 1) {
            $r = (int) ($num / 1000);
            $x = ($num / 100) % 10;
            $y = $num % 100;
            // do hundreds
            if ($x > 0) {
                $str = $ones[$x] . ' ' . (_l('num_word_hundred') === 'num_word_hundred' ? 'Hundred' : _l('num_word_hundred'));
                // do ones and tens
                $str .= $this->common_loop_indian($y, ' ' . _l('number_word_and') . ' ', '');
            } elseif ($r > 0) {
                // do ones and tens
                $str .= $this->common_loop_indian($y, ' ' . _l('number_word_and') . ' ', '');
            } else {
                // do ones and tens
                $str .= $this->common_loop_indian($y);
            }
        }
        // To display lakh and thousands
        elseif ($count == 2) {
            $r = (int) ($num / 10000);
            $x = ($num / 100) % 100;
            $y = $num % 100;
            $str .= $this->common_loop_indian($x, '', (' ' . $this->get_lakh_text($x)));
            $str .= $this->common_loop_indian($y);
            if ($str != '') {
                $str .= $triplets[$tri];
            }
        }
        // to display till hundred crore
        elseif ($count == 3) {
            $r = (int) ($num / 1000);
            $x = ($num / 100) % 10;
            $y = $num % 100;
            // do hundreds
            if ($x > 0) {
                $str = $ones[$x] . ' ' . (_l('num_word_hundred') === 'num_word_hundred' ? 'Hundred' : _l('num_word_hundred'));
                // do ones and tens
                $str .= $this->common_loop_indian($y, ' ' . _l('number_word_and') . ' ', ' Crore ');
            } elseif ($r > 0) {
                // do ones and tens
                $str .= $this->common_loop_indian($y, ' ' . _l('number_word_and') . ' ', ' Crore ');
            } else {
                // do ones and tens
                $str .= $this->common_loop_indian($y);
            }
        } else {
            $r = (int) ($num / 1000);
        }
        // add triplet modifier only if there
        // is some output to be modified...
        // continue recursing?
        if ($r > 0) {
            return $this->convert_tri_indian($r, $tri + 1) . $str;
        }

        return $str;
    }

    private function get_lakh_text($x)
    {
        $key  = $x <= 1 ? 'num_word_lakh' : 'num_word_lakhs';
        $text = _l($key);

        if ($text == $key) {
            return $x <= 1 ? 'Lakh' : 'Lakhs';
        }

        return $text;
    }
}
