<?php
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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

class SimilarBehavior extends ModelBehavior 
{
  /** Breaks a text into tokens of given length 
    * @param text Text to tokenize
    * @param tokenLen Length of each token
    * @result Array of tokens */
  function _tokenize($text, $tokenLen = 3) {
    $len = strlen($text) - $tokenLen + 1;
    $tokens = array();
    for ($i = 0; $i < $len; $i++) {
      $tokens[] = substr($text, $i, $tokenLen);
    }
    return $tokens;
  }

  /** Creates a token array which token as key and occurance count as value
    * @param text Text to tokenize
    * @param tokenLen Length of token
    * @result Array of token with occurance count as array value */
  function _tokenizeAndCount($text, $tokenLen = 3) {
    $tokens = $this->_tokenize($text, $tokenLen);
    $counts = array();
    foreach($tokens as $token) {
      if (!isset($counts[$token])) {
        $counts[$token] = 1;
      } else {
        $counts[$token] += 1;
      }
    }
    return $counts;
  }

  /** Evaluate current text with given searchTokens.
    *
    * The function counts the occurrence of search tokens in the given text.
    * @param text Given text to evaluate
    * @param searchTokens counted search tokens
    * @param tokenLen Length of each search token
    * @result count of matches */
  function _evaluate($text, $searchTokens) {
    if (!count($searchTokens)) {
      return 0;
    }
    $tokenLen = strlen(key($searchTokens));
    $textTokens = $this->_tokenizeAndCount($text, $tokenLen);
    
    $matches = 0;
    foreach ($searchTokens as $token => $count) {
      if (isset($textTokens[$token])) {
        $matches += $textTokens[$token] / $count;
      }
    }
    return $matches;
  }

  /** Search similar text through fuzzy text search through n-grams. For short
    * search terms tokens of 2, and 3 length are compared. For longer search
    * terms (string length greater as 6) tokens of 3 and 5 are used.
    *
    * @param Model Current model
    * @param searchTerm search term (string with length between 3 and 32 
    * @param field Field name to search 
    * @return array of model data, ordered by relevance. Highest first. */
  function similar(&$Model, $searchTerm, $field = 'name') {
    // Comparison is expensive. So cut long search terms
    if (strlen($searchTerm) > 32) {
      $searchTerm = substr($searchTerm, 0, 32);
    }
    $all = $Model->find('all', array('recursive' => -1));
    if (strlen($searchTerm) < 3) {
      return $all;
    }
    
    $searchTerm = strtolower(trim($searchTerm));
    if (strlen($searchTerm) > 6) {
      $searchTokensA = $this->_tokenizeAndCount($searchTerm, 5);
      $searchTokensB = $this->_tokenizeAndCount($searchTerm, 3);
    } else {
      $searchTokensA = $this->_tokenizeAndCount($searchTerm, 3);
      $searchTokensB = $this->_tokenizeAndCount($searchTerm, 2);
    }

    $match = array();
    foreach ($all as $index => $data) {
      $text = strtolower(trim($data[$Model->alias][$field]));
      $matchesA = $this->_evaluate($text, $searchTokensA);
      $matchesB = $this->_evaluate($text, $searchTokensB);
      if ($matchesA > 0 || $matchesB > 1) {
        $match[$index] = ($matchesA + 1) * ($matchesB + 1);
      }
    }
    arsort($match);
    $result = array();
    foreach ($match as $index => $levenshtein) {
      $result[] = $all[$index];
    }
    return $result;
  }
}
?>
