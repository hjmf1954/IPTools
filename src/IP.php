<?php

/**
 * The file <code>IP.php</code> implements the class IP with all the IP-functions.
 * The original file made by Alisher Safarov contains very little documentation.
 * DoxyGen documentation added.
 *
 * Please note that the IP-Class handles only simple IP-Addresses and not Network-Addresses!
 *
 * For people who wondering what the meaning of this function name:
 * - pton: a presentation(printable) format address to network address
 * - ntop: a network address to presentation(printable) format address
 *
 * @file IP.php
 * @ingroup IPTools
 * @licence GNU GPL v2+
 * @ingroup phpinclude
 * @package IPTools
 *
 * @author Safarov Alisher, Harm Frielink, Nordhorn, Germany
 * @author Safarov Alisher<alisher.safarov@outlook.com>, Harm Frielink <harm@harmfrielink.nl>
 * @copyright 2009-2018 Safarov Alisher, Harm Frielink
 *
 * @link https://github.com/S1lentium/IPTools, Forked from S1lentium/IPTools: https://github.com/hjmf1954/IPTools.git
 */

namespace IPTools;

/**
 * Class <code>IP</code> implements the Tools for manipulation IP-addresses.
 * Supports IPv4 and IPv6.
 *
 * Version:
 * - 1.0.1.0 - 17 Jan 2018 - Original version by Alisher Safarov.
 * - 1.0.1.1 - 16 Nov 2018 - Docu + Versioning.
 */
class IP {
   use PropertyTrait;

   /**
    * Versioning Number.
    */
   const versionNumber = "1.0.1.1";

   /**
    * Versioning Date.
    */
   const versionDate = 20181116;

   /**
    * Versioning, last updated.
    */
   const lastUpdated = "Fri 16 Nov 2018";

   /**
    * IP-Version 4 constant.
    */
   const IP_V4 = 'IPv4';

   /**
    * Maximum length of a IPv4 Address.
    */
   const IP_V4_MAX_PREFIX_LENGTH = 32;

   /**
    * The number of octets used in a IPv4 IP address.
    */
   const IP_V4_OCTETS = 4;

   /**
    * IP-Version 6 constant.
    */
   const IP_V6 = 'IPv6';

   /**
    * Maximum length of a IPv4 Address.
    */
   const IP_V6_MAX_PREFIX_LENGTH = 128;

   /**
    * The number of octets used in a IPv4 IP address.
    */
   const IP_V6_OCTETS = 16;

   /**
    * Placeholder for the given IP-Address.
    * @var string A packed IP-Address notation (i.e.: 192.0.0.1)
    */
   private $inAddress;

   /**
    * Constructor sets the IP-address.
    * @param  string     ip IP-Address has to be validated for IPv4 or IPv6.
    * @throws \Exception in case of an invalid IP-Address,
    */
   public function __construct($ip) {
      // Validates the given $ip address.
      if ( ! filter_var($ip, FILTER_VALIDATE_IP)) {
         throw new \Exception("[IP::IP] - Invalid IP address format.");
      }

      // Converts a human readable IP address to its packed in_addr representation.
      $this->inAddress = inet_pton($ip);
   } // __construct

   /**
    * Implements the toString.
    * Example: chr(127) . chr(0) . chr(0) . chr(1) => 127.0.0.1
    * @return string Converted packed internet address to a human readable representation.
    */
   public function __toString() {
      return inet_ntop($this->inAddress);
   } // __toString

   /**
    * Gets the maximum prefix length for a given ip in the constructor.
    * @return int the maximum length for IPv4 or IPv6.
    */
   public function getMaxPrefixLength() {
      return $this->getVersion() === self::IP_V4
         ? self::IP_V4_MAX_PREFIX_LENGTH
         : self::IP_V6_MAX_PREFIX_LENGTH;
   }

   /**
    * Gets the maximum number of octets for a given ip in the constructor.
    * @return int
    */
   public function getOctetsCount() {
      return $this->getVersion() === self::IP_V4
         ? self::IP_V4_OCTETS
         : self::IP_V6_OCTETS;
   }

   /**
    * Gets the Reverse Pointer (i.e. 192.0.0.1 -> 1.0.0.192).
    * @return string the reversed IP-Address.
    */
   public function getReversePointer() {
      if ($this->getVersion() === self::IP_V4) {
         $reverseOctets = array_reverse(explode('.', $this->__toString()));
         $reversePointer = implode('.', $reverseOctets) . '.in-addr.arpa';
      } else {
         $unpacked = unpack('H*hex', $this->inAddress);
         $reverseOctets = array_reverse(str_split($unpacked['hex']));
         $reversePointer = implode('.', $reverseOctets) . '.ip6.arpa';
      }

      return $reversePointer;
   }

   /**
    * Gets the version of the IP_Address (ipv4 or IPv6)
    * @return string one of the 2 constants for IPv4 or IPv6.
    */
   public function getVersion() {
      $version = '';

      if (filter_var(inet_ntop($this->inAddress), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
         $version = self::IP_V4;
      } elseif (filter_var(inet_ntop($this->inAddress), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
         $version = self::IP_V6;
      }
      return $version;
   }

   /**
    * Gets the inAddress.
    * @return string
    */
   public function inAddr() {
      return $this->inAddress;
   }

   /**
    * Calculates the next IP-Address by adding the $to.
    * @param  int          $to Number to be added
    * @throws \Exception
    * @return IP           The new calculated IP-Address.
    */
   public function next($to = 1) {
      if ($to < 0) {
         throw new \Exception("Number must be greater than 0");
      }

      $unpacked = unpack('C*', $this->inAddress);

      for ($i = 0; $i < $to; $i++) {
         for ($byte = count($unpacked); $byte >= 0; --$byte) {
            if ($unpacked[$byte] < 255) {
               $unpacked[$byte]++;
               break;
            }

            $unpacked[$byte] = 0;
         }
      }

      return new self(inet_ntop(call_user_func_array('pack', array_merge(array('C*'), $unpacked))));
   }

   /**
    * Parses the given IP Address (Hex, Bin, Long) and is in fact an alternative constructor.
    * @param  string ip The requested IP-Address.
    * @return IP     A new Instance of the IP-Class.
    */
   public static function parse($ip) {
      if (strpos($ip, '0x') === 0) {
         $ip = substr($ip, 2);
         return self::parseHex($ip);
      }

      if (strpos($ip, '0b') === 0) {
         $ip = substr($ip, 2);
         return self::parseBin($ip);
      }

      if (is_numeric($ip)) {
         return self::parseLong($ip);
      }

      return new self($ip);
   }

   /**
    * Parses a Binary IP_Address.
    * @param  string       $binIP
    * @throws \Exception
    * @return IP
    */
   public static function parseBin($binIP) {
      if ( ! preg_match('/^([0-1]{32}|[0-1]{128})$/', $binIP)) {
         throw new \Exception("Invalid binary IP address format");
      }

      $inAddress = '';
      foreach (array_map('bindec', str_split($binIP, 8)) as $char) {
         $inAddress .= pack('C*', $char);
      }

      return new self(inet_ntop($inAddress));
   }

   /**
    * Parses a Hexadecimal IP-Address.
    * @param  string       $hexIP
    * @throws \Exception
    * @return IP
    */
   public static function parseHex($hexIP) {
      if ( ! preg_match('/^([0-9a-fA-F]{8}|[0-9a-fA-F]{32})$/', $hexIP)) {
         throw new \Exception("Invalid hexadecimal IP address format");
      }

      return new self(inet_ntop(pack('H*', $hexIP)));
   }

   /**
    * Parses an inAddress IP-Address.
    * @param  string $inAddr Requested inAddress
    * @return IP     new IP-Address Instance.
    */
   public static function parseInAddr($inAddr) {
      return new self(inet_ntop($inAddr));
   }

   /**
    * @param  string|int $longIP  Requested long IP-address.
    * @param  string     $version The IP-Address version IPv4 or IPv6 (class constants).
    * @return IP
    */
   public static function parseLong($longIP, $version = self::IP_V4) {
      if ($version === self::IP_V4) {
         $ip = new self(long2ip($longIP));
      } else {
         $binary = array();
         for ($i = 0; $i < self::IP_V6_OCTETS; $i++) {
            $binary[] = bcmod($longIP, 256);
            $longIP = bcdiv($longIP, 256, 0);
         }
         $ip = new self(inet_ntop(call_user_func_array('pack', array_merge(array('C*'), array_reverse($binary)))));
      }

      return $ip;
   }

   /**
    * Compares 2 inet_ptons
    * @param  string $firstIP  1st IP-Address as hex, binary or numeric pton.
    * @param  string $secondIP 2nd IP-Address as hex, binary or numeric pton.
    * @return int -1, 0, 1
    * @throws Exception
    * @since 1.0.1.1 - HJMF
    * Alternative method // return strcmp( $ip1->__toString(), $ip2->__toString());
    */
   public static function strcmppton($firstIP, $secondIP) {
      $ip1 = IP::parse($firstIP);
      $ip2 = IP::parse($secondIP);

      if ($ip1->inAddress === $ip2->inAddress) {
         return 0;
      } else if ($ip1->inAddress < $ip2->inAddress) {
         return -1;
      }
      return 1;
   } // strcmppton

   /**
    * Calcualtes the previous IP-Address by substracting the $to.
    * @param  int          $to
    * @throws \Exception
    * @return IP
    */
   public function prev($to = 1) {

      if ($to < 0) {
         throw new \Exception("Number must be greater than 0");
      }

      $unpacked = unpack('C*', $this->inAddress);

      for ($i = 0; $i < $to; $i++) {
         for ($byte = count($unpacked); $byte >= 0; --$byte) {
            if ($unpacked[$byte] === 0) {
               $unpacked[$byte] = 255;
            } else {
               $unpacked[$byte]--;
               break;
            }
         }
      }

      return new self(inet_ntop(call_user_func_array('pack', array_merge(array('C*'), $unpacked))));
   }

   /**
    * Creates the Binary version of the given IP-Address.
    * @return string
    */
   public function toBin() {
      $binary = array();
      foreach (unpack('C*', $this->inAddress) as $char) {
         $binary[] = str_pad(decbin($char), 8, '0', STR_PAD_LEFT);
      }

      return implode($binary);
   }

   /**
    * Creates the Hexadecimal version of the given IP-Address.
    * @return string
    */
   public function toHex() {
      return bin2hex($this->inAddress);
   }

   /**
    * Creates the Long version of the given IP-Address.
    * @return string
    */
   public function toLong() {
      $long = 0;
      if ($this->getVersion() === self::IP_V4) {
         $long = sprintf('%u', ip2long(inet_ntop($this->inAddress)));
      } else {
         $octet = self::IP_V6_OCTETS - 1;
         foreach ($chars = unpack('C*', $this->inAddress) as $char) {
            $long = bcadd($long, bcmul($char, bcpow(256, $octet--)));
         }
      }

      return $long;
   }
} // class IP
