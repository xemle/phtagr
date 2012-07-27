<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */
class PluploadHelper extends AppHelper {
  var $helpers = array('Html');

  var $settings = array(
    'containerId' => 'upload',
    'buttonId' => 'uploadButton',
    'maxFileSize' => '1000mb',
    );

  function upload($options = array()) {
    $options = am($this->settings, $options);
    $baseUrl = h(Router::url('/'));
    $uploadUrl = h(Router::url(array('controller' => 'browser', 'action' => 'plupload')));
    $singleFile = h(__('Uploading file %s'));
    $allFiles = h(__('Uploding file %d/%d. Estimated time %s'));
    $script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    var files = new phtagr.upload.Files();
    var singleView = new phtagr.upload.SingleUploadView({el: $('#{$this->settings['containerId']} .file'), collection: files, text: '{$singleFile}'});
    var allView = new phtagr.upload.AllUploadView({el: $('#{$this->settings['containerId']} .total'), collection: files, text: '{$allFiles}'});
    $('#{$this->settings['buttonId']}').button();
    phtagr.upload.initUploader({
      runtimes : 'gears,html5,flash,silverlight',
      browse_button : 'uploadButton',
      container : 'upload',
      max_file_size : '1000mb',
      chunk_size : '1mb',
      url : '{$uploadUrl}',
      flash_swf_url : '{$baseUrl}js/plupload/plupload.flash.swf',
      silverlight_xap_url : '{$baseUrl}js/plupload/plupload.silverlight.xap',
      drop_element: 'upload-drop-area'
    }, files, function(up, params) {
      if (up.features.dragdrop) {
        $('#upload-drop-area').show();
      }
    });
    $('.file .progress').progressbar({value: 23});
    $('.total .progress').progressbar({value: 50});
  });
})(jQuery);
SCRIPT;
    $this->Html->script('underscore-min.js', array('inline' => false, 'once' => true));
    $this->Html->script('backbone-min.js', array('inline' => false, 'once' => true));
    $this->Html->script('plupload/plupload.full.js', array('inline' => false, 'once' => true));
    $this->Html->script('plupload.phtagr.js', array('inline' => false, 'once' => true));
    $this->Html->scriptBlock($script, array('inline' => false));

    $buttonText = h(__('Upload Files'));
    $dropText = h(__('Drop files here'));
    $out = <<<OUT
<div id="{$this->settings['containerId']}" class="upload">
  <div id="upload-drop-area" style="display: none;"><p>$dropText</p></div>
  <a id="{$this->settings['buttonId']}" href="#">$buttonText</a>
  <div class="upload-status">
    <div class="file" style="display: none"><p></p><div class="progress"></div></div>
    <div class="total" style="display: none"><p></p><div class="progress"></div></div>
  </div>
</div>
OUT;
    return $out;
  }

}

?>
