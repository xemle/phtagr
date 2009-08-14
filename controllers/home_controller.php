<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
class HomeController extends AppController
{
	// Important to set the davroot in the Webdav Server
	var $name = 'home';

  var $components = array('Query');
  var $helpers = array('html', 'time', 'text', 'imageData');
  var $uses = array('Media', 'Tag', 'Category', 'Comment');

  /** @todo improve the randomized newest media */
  function index() {
    $this->Query->setOrder('newest');
    $max = 50;
    $this->Query->setPageSize($max);
    $data = $this->Query->paginate();
    // generate tossed index to variy the media 
    srand(time());
    $toss = array();
    for ($i = 0; $i < count($data); $i++) {
      $toss[] = rand(0, 100);
    }
    asort($toss);
    // reassign index
    $tossData = array();
    foreach ($toss as $index => $r) {
      $tossData[] =& $data[$index];
    }
    $this->set('newMedia', $tossData);

    $this->Query->setOrder('random');
    $this->Query->setPageSize(1);
    $this->set('randomMedia', $this->Query->paginate());

    $cloud = $this->Query->getCloud(35);
    $this->set('cloudTags', $cloud);

    $cloud = $this->Query->getCloud(25, 'Category');
    $this->set('cloudCategories', $cloud);

    $acl = "1 = 1".$this->Media->buildWhereAcl($this->getUser());
    $comments = $this->Comment->findAll($acl, null, 'Comment.date DESC', 4);
    $this->set('comments', $comments);
  }
}
?>
