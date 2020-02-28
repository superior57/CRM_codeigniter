<?php

defined('BASEPATH') or exit('No direct script access allowed');

trait PDF_Signature
{
    public function process_signature()
    {
        $dimensions       = $this->getPageDimensions();
        $leftColumnExists = false;

        if (($this->type() == 'invoice' && get_option('show_pdf_signature_invoice') == 1)
        || ($this->type() == 'estimate' && get_option('show_pdf_signature_estimate') == 1)
        || ($this->type() == 'contract' && get_option('show_pdf_signature_contract') == 1)
        || ($this->type() == 'credit_note') && get_option('show_pdf_signature_credit_note') == 1) {
            $signatureImage = get_option('signature_image');

            $signaturePath   = FCPATH . 'uploads/company/' . $signatureImage;
            $signatureExists = file_exists($signaturePath);

            $blankSignatureLine = hooks()->apply_filters('blank_signature_line', '_________________________');

            if ($signatureImage != '' && $signatureExists) {
                $blankSignatureLine = '';
            }

            $this->ln(13);

            if ($signatureImage != '' && $signatureExists) {
                $blankSignatureLine .= '<br /><br /><img src="' . site_url('uploads/company/' . $signatureImage) . '" />';
            }

            $this->MultiCell(($dimensions['wk'] / 2) - $dimensions['lm'], 0, _l('authorized_signature_text') . ' ' . $blankSignatureLine, 0, 'J', 0, 0, '', '', true, 0, true, true, 0);

            $leftColumnExists = true;
        }

        $customerSignaturePath = '';

        if (isset($GLOBALS['estimate_pdf']) && !empty($GLOBALS['estimate_pdf']->signature)) {
            $estimate              = $GLOBALS['estimate_pdf'];
            $customerSignaturePath = get_upload_path_by_type('estimate') . $estimate->id . '/' . $estimate->signature;
        } elseif (isset($GLOBALS['proposal_pdf']) && !empty($GLOBALS['proposal_pdf']->signature)) {
            $proposal              = $GLOBALS['proposal_pdf'];
            $customerSignaturePath = get_upload_path_by_type('proposal') . $proposal->id . '/' . $proposal->signature;
        } elseif (isset($GLOBALS['contract_pdf']) && !empty($GLOBALS['contract_pdf']->signature)) {
            $contract              = $GLOBALS['contract_pdf'];
            $customerSignaturePath = get_upload_path_by_type('contract') . $contract->id . '/' . $contract->signature;
        }

        $customerSignaturePath = hooks()->apply_filters(
            'pdf_customer_signature_image_path',
            $customerSignaturePath,
            $this->type()
        );

        if (!empty($customerSignaturePath)) {
            $customerSignature = _l('document_customer_signature_text');

            $imageData = base64_encode(file_get_contents($customerSignaturePath));

            $customerSignature .= '<br /><br /><img src="@' . $imageData . '">';
            $width = ($dimensions['wk'] / 2) - $dimensions['rm'];

            if (!$leftColumnExists) {
                $width = $dimensions['wk'] - ($dimensions['rm'] + $dimensions['lm']);
                $this->ln(13);
            }

            $hookData = ['pdf_instance' => $this, 'type' => $this->type(), 'signatureCellWidth' => $width];

            hooks()->do_action('before_customer_pdf_signature', $hookData);
            $this->MultiCell($width, 0, $customerSignature, 0, 'R', 0, 1, '', '', true, 0, true, false, 0);
            hooks()->do_action('after_customer_pdf_signature', $hookData);
        }
    }
}
