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

App::uses('PhtagrTestCase', 'Test/Case');

class FilterManagerComponentTest  extends PhtagrTestCase {

	var $uses = array('Media', 'MyFile', 'User', 'Option');
	var $components = array('FileManager', 'FilterManager', 'Exiftool');

  var $testDir;

  var $autostartController = false;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
    parent::setUp();

    $this->testDir = $this->createTestDir();
    $this->setOptionsForExternalTools();

    $this->Option->addValue($this->FilterManager->writeEmbeddedEnabledOption, 1, 0);
    $this->Option->addValue($this->FilterManager->writeSidecarEnabledOption, 1, 0);
    $this->Option->addValue($this->FilterManager->createSidecarOption, 0, 0);
    $this->Option->addValue($this->FilterManager->createSidecarForNonEmbeddableFileOption, 0, 0);

    $admin = $this->Factory->createUser('admin', ROLE_ADMIN);
    $this->mockUser($admin);

    $this->Controller->startupProcess();
  }

  public function testReadFilesRecursivly() {
    $this->copyResource('IMG_4145.JPG', $this->testDir);
    $this->copyResource('IMG_6131.JPG', $this->testDir . 'subdir');
    $this->copyResource('IMG_7795.JPG', $this->testDir . 'subdir' . DS . 'subsubdir');

    $options = array('recursive' => false);
    $this->Controller->FilterManager->readFiles($this->testDir, $options);
    $count = $this->Media->find('count');
    $this->assertEqual($count, 1);

    $media = $this->Media->find('all');
    $names = Set::extract('/Media/name', $media);
    sort($names);
    $this->assertEqual($names, array('IMG_4145.JPG'));

    $options = array('recursive' => true);
    $this->Controller->FilterManager->readFiles($this->testDir, $options);
    $count = $this->Media->find('count');
    $this->assertEqual($count, 3);

    $media = $this->Media->find('all');
    $names = Set::extract('/Media/name', $media);
    sort($names);
    $this->assertEqual($names, array('IMG_4145.JPG', 'IMG_6131.JPG', 'IMG_7795.JPG'));
  }

  public function testDisabledWrite() {
    $this->FilterManager->writeEmbeddedEnabled = false;
    $this->FilterManager->writeSidecarEnabled = false;
    $this->FilterManager->createSidecar = false;
    $this->FilterManager->createSidecarForNonEmbeddableFile = true;

    $filename = $this->copyResource('IMG_7795.JPG', $this->testDir);
    $this->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('first');
    $data = array('Fields' => array('keywords' => 'light,night,temple,stars'));
    $tmp = $this->Media->editSingle($media, $data, $userA);
    $this->Media->save($tmp);
    $media = $this->Media->find('first');
    $filesize = filesize($filename);

    // No meta data should be written
    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);
    $this->assertEqual(filesize($filename), $filesize);

    // No new file should be created
    $Folder = new Folder($this->testDir);
    $this->assertEqual(count($Folder->find()), 1);
  }

  public function testWriteEmbedded() {
    $this->FilterManager->writeEmbeddedEnabled = true;
    $this->FilterManager->writeSidecarEnabled = false;
    $this->FilterManager->createSidecar = false;
    $this->FilterManager->createSidecarForNonEmbeddableFile = false;

    $filename = $this->copyResource('IMG_7795.JPG', $this->testDir);
    $this->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('first');
    $data = array('Fields' => array('keywords' => 'light,night,temple,stars'));
    $tmp = $this->Media->editSingle($media, $data, $userA);
    $this->Media->save($tmp);
    $media = $this->Media->find('first');
    $filesize = filesize($filename);

    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);
    $this->assertNotEqual(filesize($filename), $filesize);

    // No new files should be created
    $Folder = new Folder($this->testDir);
    $this->assertEqual(count($Folder->find()), 1);
  }

  public function testCreateSidecar() {
    $this->FilterManager->writeEmbeddedEnabled = false;
    $this->FilterManager->writeSidecarEnabled = true;
    $this->FilterManager->createSidecar = true;
    $this->FilterManager->createSidecarForNonEmbeddableFile = false;

    $filename = $this->copyResource('IMG_7795.JPG', $this->testDir);
    $this->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('first');
    $data = array('Fields' => array('keywords' => 'light,night,temple,stars'));
    $tmp = $this->Media->editSingle($media, $data, $userA);
    $this->Media->save($tmp);
    $media = $this->Media->find('first');
    $filesize = filesize($filename);

    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);
    $this->assertEqual(filesize($filename), $filesize);

    // New sidecar file should be created
    $Folder = new Folder($this->testDir);
    $files = $Folder->find();
    $this->assertEqual(count($files), 2);
    $this->assertEqual(count($Folder->find('IMG_7795.xmp')), 1);
  }

  public function testCreateSidecarForNonEmbeddableFile() {
    $this->FilterManager->writeEmbeddedEnabled = false;
    $this->FilterManager->writeSidecarEnabled = true;
    $this->FilterManager->createSidecar = false;
    $this->FilterManager->createSidecarForNonEmbeddableFile = true;

    $filename = $this->copyResource('MVI_7620.OGG', $this->testDir);
    $this->FilterManager->readFiles($this->testDir);
    $media = $this->Media->find('first');
    $data = array('Fields' => array('keywords' => 'beach'));
    $tmp = $this->Media->editSingle($media, $data, $userA);
    $this->Media->save($tmp);
    $media = $this->Media->find('first');
    $filesize = filesize($filename);

    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);
    $this->assertEqual(filesize($filename), $filesize);

    // New sidecar file should be created
    $Folder = new Folder($this->testDir);
    $files = $Folder->find();
    $this->assertEqual(count($files), 2);
    $this->assertEqual(count($Folder->find('MVI_7620.xmp')), 1);
  }

  public function testDisabledSidecarCreation() {
    $this->FilterManager->writeEmbeddedEnabled = false;
    $this->FilterManager->writeSidecarEnabled = true;
    $this->FilterManager->createSidecar = false;
    $this->FilterManager->createSidecarForNonEmbeddableFile = false;

    $filename = $this->copyResource('IMG_7795.JPG', $this->testDir);
    $this->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('first');
    $data = array('Fields' => array('keywords' => 'light,night,temple,stars'));
    $tmp = $this->Media->editSingle($media, $data, $userA);
    $this->Media->save($tmp);
    $media = $this->Media->find('first');
    $filesize = filesize($filename);

    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);
    $this->assertEqual(filesize($filename), $filesize);

    // New sidecar file should be created
    $Folder = new Folder($this->testDir);
    $this->assertEqual(count($Folder->find()), 1);
  }

  public function testWriteEmbeddedAndSidecar() {
    $this->FilterManager->writeEmbeddedEnabled = true;
    $this->FilterManager->writeSidecarEnabled = true;
    $this->FilterManager->createSidecar = true;
    $this->FilterManager->createSidecarForNonEmbeddableFile = false;

    $imageFile = $this->copyResource('IMG_7795.JPG', $this->testDir);
    $sidecarFile = $this->copyResource('IMG_7795.xmp', $this->testDir);
    $this->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('first');
    $this->assertEqual(count($media['File']), 2);

    $data = array('Fields' => array('keywords' => 'light,night,temple,stars'));
    $tmp = $this->Media->editSingle($media, $data, $userA);
    $this->Media->save($tmp);
    $media = $this->Media->find('first');
    $imageFileSize = filesize($imageFile);
    $sidecarFileSize = filesize($sidecarFile);

    // Image and sidecar file should be updated
    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);
    $this->assertNotEqual(filesize($imageFile), $imageFileSize);
    $this->assertNotEqual(filesize($sidecarFile), $sidecarFileSize);

    // Now new files should be created
    $Folder = new Folder($this->testDir);
    $this->assertEqual(count($Folder->find()), 2);
  }

}
