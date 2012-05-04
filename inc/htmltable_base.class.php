<?php
/*
 * @version $Id: HEADER 15930 2011-10-25 10:47:55Z jmd $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class HTMLTable_UnknownHeader       extends Exception {}
class HTMLTable_UnknownHeaders      extends Exception {}
class HTMLTable_UnknownHeadersOrder extends Exception {}

/**
 * @since version 0.84
**/
abstract class HTMLTable_Base  {

   private $headers = array();
   private $headers_order = array();
   private $headers_sub_order = array();
   private $super;


   /**
    * @param $super
   **/
   function __construct($super) {
      $this->super = $super;
   }


   /**
    * @param $header_object         HTMLTable_Header object
    * @param $allow_super_header    (false by default
   **/
   function appendHeader(HTMLTable_Header $header_object, $allow_super_header=false) {

      if (!$header_object instanceof HTMLTable_Header) {
         throw new Exception('Implementation error: appendHeader requires HTMLTable_Header as parameter');
      }
      $header_object->getHeaderAndSubHeaderName($header_name, $subHeader_name);
      if ($header_object->isSuperHeader()
          && (!$this->super)
          && (!$allow_super_header)) {
         throw new Exception(sprintf('Implementation error : invalid super header name "%s"',
                                     $header_name));
      }
      if (!$header_object->isSuperHeader()
          && $this->super) {
         throw new Exception(sprintf('Implementation error : invalid super header name "%s"',
                                     $header_name));
      }

      if (!isset($this->headers[$header_name])) {
         $this->headers[$header_name]           = array();
         $this->headers_order[]                 = $header_name;
         $this->headers_sub_order[$header_name] = array();
      }
      if (!isset($this->headers[$header_name][$subHeader_name])) {
         $this->headers_sub_order[$header_name][] = $subHeader_name;
      }
      $this->headers[$header_name][$subHeader_name] = $header_object;
      return $header_object;
   }


   /**
    * Internal test to see if we can add an header. For instance, we can only add a super header
    * to a table if there is no group defined. And we can only create a sub Header to a group if
    * it contains no row
   **/
   abstract function tryAddHeader();


   /**
    * create a new HTMLTable_Header
    *
    * Depending of "$this" type, this head will be an HTMLTable_SuperHeader of a HTMLTable_SubHeader
    *
    * @param $name      string            The name that can be refered by getHeaderByName()
    * @param $content   string or array   The content (see HTMLTable_Entity#content) of the header
    * @param $super                       HTMLTable_SuperHeader object:
    *                                     the header that contains this new header only used
    *                                     for HTMLTable_SubHeader (default NULL)
    *                                     (ie: $this instanceof HTMLTable_Group)
    * @param $father                      HTMLTable_Header object: the father of the current header
    *                                     (default NULL)
    *
    * @exception Exception                If there is no super header while creating a sub
    *                                     header or a super header while creating a super one
    *
    * @return the HTMLTable_Header        that have been created
   **/
   function addHeader($name, $content, HTMLTable_SuperHeader $super=NULL,
                      HTMLTable_Header $father=NULL) {

      $this->tryAddHeader();
      if (is_null($super)) {
         if (!$this->super) {
            throw new Exception('A sub header requires a super header');
         }
         return $this->appendHeader(new HTMLTable_SuperHeader($this, $name, $content,
                                                              $father));
      }
      if ($this->super) {
         throw new Exception('Cannot attach a super header to another header');
      }
      return $this->appendHeader(new HTMLTable_SubHeader($super, $name, $content, $father));
   }


   /**
    * @param $name
   **/
   function getSuperHeaderByName($name) {
      return $this->getHeaderByName($name, '');
   }


   /**
    * @param $name
    * @param $sub_name (default NULL)
   **/
   function getHeaderByName($name, $sub_name=NULL) {

      if (is_string($sub_name)) {
         if (isset($this->headers[$name][$sub_name])) {
            return $this->headers[$name][$sub_name];
         }
         throw new HTMLTable_UnknownHeader($name.':'.$sub_name);
      }

      foreach ($this->headers as $header) {
         if (isset($header[$name])) {
            return $header[$name];
         }
      }
      throw new HTMLTable_UnknownHeader($name);
   }


   /**
    * @param $header_name  (default '')
   **/
   function getHeaders($header_name='') {

      if (empty($header_name)) {
         return $this->headers;
      }
      if (isset($this->headers[$header_name])) {
         return $this->headers[$header_name];
      }
      throw new HTMLTable_UnknownHeaders($header_name);
   }


   /**
    * @param $header_name  (default '')
   **/
   function getHeaderOrder($header_name='') {

      if (empty($header_name)) {
         return $this->headers_order;
      }
      if (isset($this->headers_sub_order[$header_name])) {
         return $this->headers_sub_order[$header_name];
      }
      throw new  HTMLTable_UnknownHeadersOrder($header_name);

   }
}
?>
