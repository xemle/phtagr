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

class SimilarBehavior extends ModelBehavior {

  /**
   * Breaks a text into tokens of given length
   *
   * @param text Text to tokenize
   * @param tokenLen Length of each token
   * @return Array of tokens
   */
  function _tokenize($text, $tokenLen = 3) {
    $len = strlen($text) - $tokenLen + 1;
    $tokens = array();
    for ($i = 0; $i < $len; $i++) {
      $tokens[] = substr($text, $i, $tokenLen);
    }
    return $tokens;
  }

  /**
   * Creates a token array which token as key and occurance count as value
   *
   * @param text Text to tokenize
   * @param tokenLen Length of token
   * @return Array of token with occurance count as array value
   */
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

  /**
   * Evaluate current text with given searchTokens.
   *
   * The function counts the occurrence of search tokens in the given text.
   * @param text Given text to evaluate
   * @param searchTokens counted search tokens
   * @param tokenLen Length of each search token
   * @return count of matches
   */
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

  /**
   * Search similar text through fuzzy text search through n-grams. For short
   * search terms tokens of 2, and 3 length are compared. For longer search
   * terms (string length greater as 6) tokens of 3 and 5 are used.
   *
   * @param Model Current model
   * @param searchTerm search term (string with length between 3 and 32
   * @param field Field name to search
   * @return array of model data, ordered by relevance. Highest first.
   */
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