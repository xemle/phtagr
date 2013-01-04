<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */


App::uses('BaseFilter', 'Component');

class SidecarFilterComponent extends BaseFilterComponent {
  var $controller = null;
  var $components = array('Command', 'FileManager', 'Command', 'Exiftool');

  var $fieldMapIPTC = array(
      'keyword' => 'Keywords',
      'category' => 'SupplementalCategories',
      'sublocation' => 'Sub-location',
      'city' => 'City',
      'state' => 'Province-State',
      'country' => 'Country-PrimaryLocationName'
      );
  var $fieldMapXMP = array(
      'keyword' => 'Subject',
      'category' => 'SupplementalCategories',
      'sublocation' => 'Location',
      'city' => 'City',
      'state' => 'State',
      'country' => 'Country'
      );

  public function getName() {
    return "Sidecar";
  }

  public function getExtensions() {
    return array('xmp' => array('priority' => 5));
  }

  public function getSidecarFilename($filename) {
    return substr($filename, 0, strrpos($filename, '.') + 1) . 'xmp';
  }

  private function _findOrCreate($filename, $createSidecar) {
    $xmpFilename = $this->getSidecarFilename($filename);
    if (file_exists($xmpFilename)) {
      return $xmpFilename;
    }
    if ($createSidecar){
      return $this->create($filename);
    } else {
      return false;
    }
  }

  public function hasSidecar($filename, $createSidecar = false) {

    if (!$this->controller->getOption('xmp.use.sidecar', 0)) {
      return false;
    }

    $fileOfMedia = $this->MyFile->findByFilename($filename);
    if (!$fileOfMedia || empty($fileOfMedia['File']['media_id'])) {//['Media']['id']
      Logger::err("Could not find File with filename: $filename");
      return false;
    }
    $media = $this->Media->findById($fileOfMedia['Media']['id']);

    $xmpFilename = $this->_findOrCreate($filename, $createSidecar);
    if (!$xmpFilename) {
      return false;
    }

    $sidecar = $this->MyFile->findByFilename($xmpFilename);
    if (!$sidecar){
      $this->FileManager->add($xmpFilename);
      $sidecar = $this->MyFile->findByFilename($xmpFilename);
      Logger::info("Added sidecar file to database: $xmpFilename");
    }

    $mediaId = $media['Media']['id'];
    if (!isset($sidecar['media_id'])) {
      //add media to sidecar file
      $sidecar = $this->MyFile->findByFilename($xmpFilename);
      if (!$this->controller->MyFile->setMedia($sidecar, $mediaId)) {
        Logger::err("File was not saved: " . $xmpFilename);
        $this->FilterManager->addError($xmpFilename, "FileSaveError");
        return false;
      }
    } elseif ($mediaId !== $sidecar['File']['media_id']) {
      //it exists but does not belong to this media
      return false;
    }

    //$this->controller->MyFile->updateReaded($sidecar);
    $this->controller->MyFile->setFlag($sidecar, FILE_FLAG_DEPENDENT);

    return true;
  }

  /**
   * Write empty XMP file
   *
   * @param string $filename Filename of XMP
   * @return true on success
   */
  public function _createEmpty($filename) {
    $output = '<?xpacket begin="?" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/" >
   <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
   </rdf:RDF>
</x:xmpmeta>
<?xpacket end="w"?>';
    $File = new File($filename);
    return $File->write($output);
  }

  /**
   * Create a XMP sidecar from given filename by using exiftool. If exiftoll
   * could not read given original file an empty XMP file is created.
   *
   * @param string $filename Original file
   * @return Sidecare filename on success
   */
  public function create($filename) {
    if (!$this->Exiftool->isEnabled()) {
      return false;
    }
    $xmpFilename = $this->getSidecarFilename($filename);

    if (file_exists($xmpFilename)) {
      $this->FileManager->add($xmpFilename);
      return $xmpFilename;
    }

    if (!is_writable(dirname($filename))) {
      Logger::warn("Cannot create file sidecar. Directory of media is not writeable");
      return false;
    }

    $args[] = "-tagsFromFile";
    $args[] = $filename;
    $args[] = $xmpFilename ;
    $result = $this->Exiftool->writeMetaData($xmpFilename, $args);

    //wait until 1 sec if file is not created yet
    //without the loop it fails condition file_exists($xmpFilename)
    $starttime = microtime(true);
    while (!file_exists($xmpFilename) && (round((microtime(true) - $starttime), 4)<1)) {
      //nanospllep is not available on windows systems
      time_nanosleep(0, 1000000); // 1 ms to avoid high cpu utilisation; 0.01 is too slow
    }

    if ($result != true || !file_exists($xmpFilename)) {
      Logger::err("Could not create sidecar file from original file '$filename': " . join(", ", (array)$result));
      if (!$this->_createEmpty($xmpFilename)) {
        Logger::err("Could not create empty sidecar '$xmpFilename' file from original file '$filename'");
        return false;
      } else {
        Logger::info("Created empty xmp sidecar file: $xmpFilename");
      }
    } else {
      Logger::info("Created xmp sidecar file from '$filename': $xmpFilename");
    }

    $this->FileManager->add($xmpFilename);
    return $xmpFilename;
  }

  /**
   * Finds the MainFile of a sidecar
   *
   * @param video File model data of the video
   * @return media of the MainFile file. False if no MainFile file was found
   */
  public function _findMainFile($sidecar) {
    $sidecarFilename = $this->controller->MyFile->getFilename($sidecar);
    $path = dirname($sidecarFilename);
    $folder = new Folder($path);
    $pattern = basename($sidecarFilename);
    $ExtensionsList = $this->FilterManager->getExtensions();

    $pattern = substr($pattern, 0, strrpos($pattern, '.')+1).'('.implode($ExtensionsList, '|').')';
    $found = $folder->find($pattern);
    asort($found);
    if (!count($found)) {
      return false;
    }
    foreach ($found as $file) {
      $filename = Folder::addPathElement($path, $file);
      if (is_readable($filename)) {
        $MainFile = $this->FilterManager->findFileInPath($path, $filename);
        if ($MainFile) {
          //Err: it finds first file in database, not the first file found on hdd;
          //maybe a higher priority file exists on hdd but it is not imported yet (video and jpg with same basename)
          //possible to have higher priority and missing from database?
          //todo:?:check just the first $file in $found, after sorting by ext priority?
          return $MainFile;
        }
      }
    }
    return false;
  }


  /**
   * Read the meta data from the file
   *
   * @param file File model data
   * @param media Reference of Media model data
   * @param options Options
   *  - noSave if set dont save model data
   * @return mixed The image data array or False on error
   */
  public function read(&$file, &$media = null, $options = array(), &$pipes = null) {
    $options = am(array('noSave' => false), $options);
    $filename = $this->MyFile->getFilename($file);

    if (!$this->controller->MyFile->isType($file, FILE_TYPE_SIDECAR) ||
        !$this->Exiftool->isEnabled()) {
      return false;
    }

    $path = Folder::slashTerm(dirname($filename));
    if (!$media){
      //no media attached yet;
      //search if media can be attached
      $mainfileOfMedia = $this->_findMainFile($file);
      if (!isset($mainfileOfMedia['Media']['id'])) {
        return false;
      } else {
        $mediaId = $mainfileOfMedia['Media']['id'];
        $mediaMainFilename = $mainfileOfMedia['File']['path'].$mainfileOfMedia['File']['file'];
        //$media = $this->Media->findById($mediaId);
        $media = $this->FilterManager->_findMediaInPath($path, $mediaMainFilename);
        // attach sidecar file to media
        if (!$this->controller->MyFile->setMedia($file, $mediaId)) {
          Logger::err("File was not saved: " . $filename);
          $this->FilterManager->addError($filename, "FileSaveError");
          return false;
        }
      }
    }

    $meta = $this->Exiftool->readMetaData($filename);

    if ($meta === false) {
      $this->FilterManager->addError($filename, 'NoMetaDataFound');
      return false;
    }

    //possible errors if sidecar was created outside phtagr (not all meta fields available):
    //all needed meta should be in sidecar file, otherwise missing fields in sidecar will be replaced by default values
    //TODO: only available meta should be processed
    //missing sidecar PhtagrGroups should not delete current groups of media
    //empty PhtagrGroups should delete current groups of media
    //idem for all fields
    $this->Exiftool->extractImageDataSidecar($media, $meta);

    if ($options['noSave']) {
      return $media;
    } elseif (!$this->Media->save($media)) {
      Logger::err("Could not save Media");
      Logger::trace($media);
      $this->FilterManager->addError($filename, 'MediaSaveError');
      return false;
    }
    $this->FilterManager->_replaceInCache($path, $media, 'Media');

    Logger::verbose("Updated media (id ".$media['Media']['id'].")");

    $this->controller->MyFile->update($file);//fix for permanent changed outside
    $this->controller->MyFile->updateReaded($file);
    $this->controller->MyFile->setFlag($file, FILE_FLAG_DEPENDENT);
    return $media;
  }

  /**
   * Write the meta data to an image file
   *
   * @param file File model data
   * @param media Media model data
   * @param options Array of options
   * @return mixed False on error
   */
  public function write(&$file, &$media, $options = array()) {
    if (!$file || !$media) {
      Logger::err("File or media is empty");
      return false;
    }
    if (!$this->controller->getOption('bin.exiftool')) {
      Logger::err("Exiftool is not defined. Abored writing of meta data");
      return false;
    }
    $filename = $this->controller->MyFile->getFilename($file);
    if (!file_exists($filename) || !is_writeable(dirname($filename)) || !is_writeable($filename)) {
      $id = isset($media['Media']['id']) ? $media['Media']['id'] : 0;
      Logger::warn("File: $filename (#$id) does not exists nor is readable");
      return false;
    }

    $data = $this->Exiftool->readMetaData($filename);
    if ($data === false) {
      Logger::warn("File has no metadata!");
      return false;
    }

    $args = $this->Exiftool->createExportArguments($data, $media, $filename);
    if (!count($args)) {
      Logger::debug("File '$filename' has no metadata changes");
      if (!$this->Media->deleteFlag($media, MEDIA_FLAG_DIRTY)) {
        Logger::warn("Could not update image data of media {$media['Media']['id']}");
      }
      return true;
    }

    $result = $this->Exiftool->writeMetaData($filename, $args);
    if ($result !== true) {
      Logger::warn("Could not write meta data. Result is " . join(", ", (array) $result));
      return false;
    }

    $this->controller->MyFile->update($file);
    if (!$this->Media->deleteFlag($media, MEDIA_FLAG_DIRTY)) {
      $this->controller->warn("Could not update image data of media {$media['Media']['id']}");
    }
    return true;
  }

}
