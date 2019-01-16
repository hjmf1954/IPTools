<?php
/**
 * File <code>IntegrationTest.php</code> contains common requests for the IPTools factory.
 * The UnitTests are specially made for Whois operations for:
 * - Afranic : Africa.
 * - Arnic   : Asia and Pacific.
 * - Lacnic  : Latin America.
 * - Ripe    : Europe.
 *
 *  The sequence for testing:
 *  - IP-Class
 *  - Network-Class
 *  - Range-Class
 *  - Integration-Test testing the integration between all 3-classes.
 *
 * See for more information:
 * - https://www.ipaddressguide.com/ipv6-cidr
 *
 * @author Harm Frielink, Nordhorn, Germany
 * @author Harm Frielink <harm@harmfrielink.nl>
 * @copyright 2018-2018 Harm Frielink

 */

use IPTools\IP;
use IPTools\Network;
use IPTools\Range;

/**
 * Class <code>IntegrationTest</code> tests the integration of the name-space IPTools.
 *
 * Version
 * - 1.0.1.0 - 18 Nov 2018 - Introduction in IPTools
 */
class IntegrationTest extends \PHPUnit_Framework_TestCase {

   /**
    * Tests all versions of the involved Classes.
    */
   public function testVersions() {
      $this->assertEquals( "1.0.1.1", IP::versionNumber     , "Not correct versionNumber");
      $this->assertEquals( "1.0.1.1", Network::versionNumber, "Not correct versionNumber");
      $this->assertEquals( "1.0.1.1", Range::versionNumber  , "Not correct versionNumber");
   } // testVersions

   /**
    * Tests the conversion of
    * - Wild-cards (IPv4 only) to CIDR notation. (i.e. 192.168.1.*)
    * - Ranges of IPv4 and IPv6 to CIDR notation.
    * @dataProvider getRange2CIDR
    */
   public function testRange2CIDR($data, $expected) {

      $result = array();
      foreach (Range::parse($data)->getNetworks() as $network) {
         $result[] = (string) $network;
      } // foreach

      $this->assertEquals($expected, $result);
   }  // testRange2CIDR

   /**
    * Tests the parsing of a CIDR IPv4 or IPv6 notation into a range.
    * @param  array $data     String 192.168.0/24
    * @param  array $expected Range
    * @dataProvider getCIDR2Range
    */
   public function testCIDR2Range($data, $expected) {
      $range = Range::parse($data);

      $this->assertEquals( IP::strcmppton($expected[0], $range->firstIP ), 0);
      $this->assertEquals( IP::strcmppton($expected[1], $range->lastIP  ), 0);
   }  // testCIDR2Range

   public function test4Whois() {
      $iniRow   = "222.168.0.0    , 222.169.255.255 = Description";
      $aRow     = explode("=", $iniRow);
      $iniRange = $aRow[0];
      $aR       = explode(",", $iniRange);
      $aRange   = array( trim($aR[0]), trim($aR[1]));
      $range1   = Range::parse( sprintf("%s-%s", $aRange[0], $aRange[1]));
      $range2   = new Range( IP::parse($aRange[0]), IP::parse($aRange[1]));
      $this->assertEquals( $range1, $range2);

      // Alternative method
      $row      = str_replace(' ', '' , $iniRow);
      $row      = str_replace(',', '-', $row   );
      $row      = substr($row, 0, strpos($row, "="));
      $range3   = Range::parse($row);
      $this->assertEquals( $range1, $range3);

      // Tests the contain
      $ip = IP::parse("222.168.1.0"); $this->assertTrue ( $range1->contains($ip));
      $ip = IP::parse("222.170.1.0"); $this->assertFalse( $range1->contains($ip));
   }


   /**
    * Getter for 'testRange2CIDR'.
    * @see testRange2CIDR
    */
   public function getRange2CIDR() {
      return array(
         array('192.168.1.*'                 , array('192.168.1.0/24')),   // Wild-card
         array('192.168.*.*'                 , array('192.168.0.0/16')),   // Wild-card
         array('192.*.*.*'                   , array('192.0.0.0/8'   )),   // Wild-card
         array('192.168.1.208-192.168.1.255',                              // Ranges
            array(
               '192.168.1.208/28',
               '192.168.1.224/27',
            )
         ),
         array('192.168.1.0-192.168.1.191',
            array(
               '192.168.1.0/25',
               '192.168.1.128/26',
            )
         ),
         array('192.168.1.125-192.168.1.126',
            array(
               '192.168.1.125/32',
               '192.168.1.126/32',
            )
         ),
         array('2001:db8::3e81:d8ff:feef:0-2001:db8::3e81:d8ff:feef:ffff',
            array('2001:db8::3e81:d8ff:feef:0/112')
         ),
         array('2001:db8::3e81:d8ff:feee:0-2001:db8::3e81:d8ff:feef:ffff',
            array(
               '2001:db8::3e81:d8ff:feee:0/111'
            )
         ),
         array('2001:0DB8:0000:0000:3E81:D8FF:FEEE:0000-2001:0DB8:0000:0000:3E81:D8FF:FEEE:00FF',
            array(
               '2001:db8::3e81:d8ff:feee:0/120'
            )
         )
      );
   } // getRange2CIDR

   /**
    * Getter for the 'testCIDR2Range'.
    * @see testCIDR2Range
    * @return array testdata
    */
   public function getCIDR2Range() {
      return array(
         array('127.0.0.1-127.255.255.255'      , array('127.0.0.1', '127.255.255.255') ),   // CIDR
         array('2001:db8::3e81:d8ff:feee:0/120' ,                                            // CIDR
            array('2001:0DB8::3E81:D8FF:FEEE:0000', '2001:0DB8::3E81:D8FF:FEEE:00FF'),
         ),
         array('2001:db8::3e81:d8ff:feee:0/127',
            array('2001:0DB8:0000:0000:3E81:D8FF:FEEE:0000', '2001:0DB8:0000:0000:3E81:D8FF:FEEE:0001'),
         )
      );
   } // getCIDR2Range


} // class IntegrationTest