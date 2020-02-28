<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<p class="bold"><?php echo _l('proposal'); ?></p>
<?php render_yes_no_option('proposal_accept_identity_confirmation','accept_identity_confirmation_and_signature_sign'); ?>
<hr />
<p class="bold"><?php echo _l('estimate'); ?></p>
<?php render_yes_no_option('estimate_accept_identity_confirmation','accept_identity_confirmation_and_signature_sign'); ?>
<hr />
<?php echo render_textarea('settings[e_sign_legal_text]','legal_bound_text',get_option('e_sign_legal_text')); ?>
