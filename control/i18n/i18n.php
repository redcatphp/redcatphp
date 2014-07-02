<?php namespace surikat\control\i18n;
use surikat\control;
require_once __DIR__.'/php-gettext.php';
class i18n {
	private static $locales_root;
	private static $domain;
	private static $locale;
	private static $language;
	static $availables = array('fr','en');
	static $default_lang = 'fr';
	static $i18n_iso = array (
	  'AF' =>  array (
			'name' => 'AFGHANISTAN',
			'A2' => 'AF',
			'A3' => 'AFG',
			'number' => '004',
		  ),
		  'AX
		' =>  array (
			'name' => 'ALAND ISLANDS',
			'A2' => 'AX
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'AL' =>  array (
			'name' => 'ALBANIA',
			'A2' => 'AL',
			'A3' => 'ALB',
			'number' => '008',
		  ),
		  'DZ' =>  array (
			'name' => 'ALGERIA',
			'A2' => 'DZ',
			'A3' => 'DZA',
			'number' => '012',
		  ),
		  'AS' =>  array (
			'name' => 'AMERICAN SAMOA',
			'A2' => 'AS',
			'A3' => 'ASM',
			'number' => '016',
		  ),
		  'AD' =>  array (
			'name' => 'ANDORRA',
			'A2' => 'AD',
			'A3' => 'AND',
			'number' => '020',
		  ),
		  'AO' =>  array (
			'name' => 'ANGOLA',
			'A2' => 'AO',
			'A3' => 'AGO',
			'number' => '024',
		  ),
		  'AI' =>  array (
			'name' => 'ANGUILLA',
			'A2' => 'AI',
			'A3' => 'AIA',
			'number' => '660',
		  ),
		  'AQ' =>  array (
			'name' => 'ANTARCTICA',
			'A2' => 'AQ',
			'A3' => 'ATA',
			'number' => '010',
		  ),
		  'AG' =>  array (
			'name' => 'ANTIGUA AND BARBUDA',
			'A2' => 'AG',
			'A3' => 'ATG',
			'number' => '028',
		  ),
		  'AR' =>  array (
			'name' => 'ARGENTINA',
			'A2' => 'AR',
			'A3' => 'ARG',
			'number' => '032',
		  ),
		  'AM' =>  array (
			'name' => 'ARMENIA',
			'A2' => 'AM',
			'A3' => 'ARM',
			'number' => '051',
		  ),
		  'AW' =>  array (
			'name' => 'ARUBA',
			'A2' => 'AW',
			'A3' => 'ABW',
			'number' => '533',
		  ),
		  'AU' =>  array (
			'name' => 'AUSTRALIA',
			'A2' => 'AU',
			'A3' => 'AUS',
			'number' => '036',
		  ),
		  'AT' =>  array (
			'name' => 'AUSTRIA',
			'A2' => 'AT',
			'A3' => 'AUT',
			'number' => '040',
		  ),
		  'AZ' =>  array (
			'name' => 'AZERBAIJAN',
			'A2' => 'AZ',
			'A3' => 'AZE',
			'number' => '031',
		  ),
		  'BS' =>  array (
			'name' => 'BAHAMAS',
			'A2' => 'BS',
			'A3' => 'BHS',
			'number' => '044',
		  ),
		  'BH' =>  array (
			'name' => 'BAHRAIN',
			'A2' => 'BH',
			'A3' => 'BHR',
			'number' => '048',
		  ),
		  'BD' =>  array (
			'name' => 'BANGLADESH',
			'A2' => 'BD',
			'A3' => 'BGD',
			'number' => '050',
		  ),
		  'BB' =>  array (
			'name' => 'BARBADOS',
			'A2' => 'BB',
			'A3' => 'BRB',
			'number' => '052',
		  ),
		  'BY' =>  array (
			'name' => 'BELARUS',
			'A2' => 'BY',
			'A3' => 'BLR',
			'number' => '112',
		  ),
		  'BE' =>  array (
			'name' => 'BELGIUM',
			'A2' => 'BE',
			'A3' => 'BEL',
			'number' => '056',
		  ),
		  'BZ' =>  array (
			'name' => 'BELIZE',
			'A2' => 'BZ',
			'A3' => 'BLZ',
			'number' => '084',
		  ),
		  'BJ' =>  array (
			'name' => 'BENIN',
			'A2' => 'BJ',
			'A3' => 'BEN',
			'number' => '204',
		  ),
		  'BM' =>  array (
			'name' => 'BERMUDA',
			'A2' => 'BM',
			'A3' => 'BMU',
			'number' => '060',
		  ),
		  'BT' =>  array (
			'name' => 'BHUTAN',
			'A2' => 'BT',
			'A3' => 'BTN',
			'number' => '064',
		  ),
		  'BO' =>  array (
			'name' => 'BOLIVIA',
			'A2' => 'BO',
			'A3' => 'BOL',
			'number' => '068',
		  ),
		  'BA' =>  array (
			'name' => 'BOSNIA AND HERZEGOWINA',
			'A2' => 'BA',
			'A3' => 'BIH',
			'number' => '070',
		  ),
		  'BW' =>  array (
			'name' => 'BOTSWANA',
			'A2' => 'BW',
			'A3' => 'BWA',
			'number' => '072',
		  ),
		  'BV' =>  array (
			'name' => 'BOUVET ISLAND',
			'A2' => 'BV',
			'A3' => 'BVT',
			'number' => '074',
		  ),
		  'BR' =>  array (
			'name' => 'BRAZIL',
			'A2' => 'BR',
			'A3' => 'BRA',
			'number' => '076',
		  ),
		  'IO' =>  array (
			'name' => 'BRITISH INDIAN OCEAN TERRITORY',
			'A2' => 'IO',
			'A3' => 'IOT',
			'number' => '086',
		  ),
		  'BN' =>  array (
			'name' => 'BRUNEI DARUSSALAM',
			'A2' => 'BN',
			'A3' => 'BRN',
			'number' => '096',
		  ),
		  'BG' =>  array (
			'name' => 'BULGARIA',
			'A2' => 'BG',
			'A3' => 'BGR',
			'number' => '100',
		  ),
		  'BF' =>  array (
			'name' => 'BURKINA FASO',
			'A2' => 'BF',
			'A3' => 'BFA',
			'number' => '854',
		  ),
		  'BI' =>  array (
			'name' => 'BURUNDI',
			'A2' => 'BI',
			'A3' => 'BDI',
			'number' => '108',
		  ),
		  'KH' =>  array (
			'name' => 'CAMBODIA',
			'A2' => 'KH',
			'A3' => 'KHM',
			'number' => '116',
		  ),
		  'CM' =>  array (
			'name' => 'CAMEROON',
			'A2' => 'CM',
			'A3' => 'CMR',
			'number' => '120',
		  ),
		  'CA' =>  array (
			'name' => 'CANADA',
			'A2' => 'CA',
			'A3' => 'CAN',
			'number' => '124',
		  ),
		  'CV' =>  array (
			'name' => 'CAPE VERDE',
			'A2' => 'CV',
			'A3' => 'CPV',
			'number' => '132',
		  ),
		  'KY' =>  array (
			'name' => 'CAYMAN ISLANDS',
			'A2' => 'KY',
			'A3' => 'CYM',
			'number' => '136',
		  ),
		  'CF' =>  array (
			'name' => 'CENTRAL AFRICAN REPUBLIC',
			'A2' => 'CF',
			'A3' => 'CAF',
			'number' => '140',
		  ),
		  'TD' =>  array (
			'name' => 'CHAD',
			'A2' => 'TD',
			'A3' => 'TCD',
			'number' => '148',
		  ),
		  'CL' =>  array (
			'name' => 'CHILE',
			'A2' => 'CL',
			'A3' => 'CHL',
			'number' => '152',
		  ),
		  'CN' =>  array (
			'name' => 'CHINA',
			'A2' => 'CN',
			'A3' => 'CHN',
			'number' => '156',
		  ),
		  'CX' =>  array (
			'name' => 'CHRISTMAS ISLAND',
			'A2' => 'CX',
			'A3' => 'CXR',
			'number' => '162',
		  ),
		  'CC' =>  array (
			'name' => 'COCOS (KEELING) ISLANDS',
			'A2' => 'CC',
			'A3' => 'CCK',
			'number' => '166',
		  ),
		  'CO' =>  array (
			'name' => 'COLOMBIA',
			'A2' => 'CO',
			'A3' => 'COL',
			'number' => '170',
		  ),
		  'KM' =>  array (
			'name' => 'COMOROS',
			'A2' => 'KM',
			'A3' => 'COM',
			'number' => '174',
		  ),
		  'CG' =>  array (
			'name' => 'CONGO',
			'A2' => 'CG',
			'A3' => 'COG',
			'number' => '178',
		  ),
		  'CD
		' =>  array (
			'name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE',
			'A2' => 'CD
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'CK' =>  array (
			'name' => 'COOK ISLANDS',
			'A2' => 'CK',
			'A3' => 'COK',
			'number' => '184',
		  ),
		  'CR' =>  array (
			'name' => 'COSTA RICA',
			'A2' => 'CR',
			'A3' => 'CRI',
			'number' => '188',
		  ),
		  'CI' =>  array (
			'name' => 'COTE D\'IVOIRE',
			'A2' => 'CI',
			'A3' => 'CIV',
			'number' => '384',
		  ),
		  'HR' =>  array (
			'name' => 'CROATIA',
			'A2' => 'HR',
			'A3' => 'HRV',
			'number' => '191',
		  ),
		  'CU' =>  array (
			'name' => 'CUBA',
			'A2' => 'CU',
			'A3' => 'CUB',
			'number' => '192',
		  ),
		  'CY' =>  array (
			'name' => 'CYPRUS',
			'A2' => 'CY',
			'A3' => 'CYP',
			'number' => '196',
		  ),
		  'CZ' =>  array (
			'name' => 'CZECH REPUBLIC',
			'A2' => 'CZ',
			'A3' => 'CZE',
			'number' => '203',
		  ),
		  'DK' =>  array (
			'name' => 'DENMARK',
			'A2' => 'DK',
			'A3' => 'DNK',
			'number' => '208',
		  ),
		  'DJ' =>  array (
			'name' => 'DJIBOUTI',
			'A2' => 'DJ',
			'A3' => 'DJI',
			'number' => '262',
		  ),
		  'DM' =>  array (
			'name' => 'DOMINICA',
			'A2' => 'DM',
			'A3' => 'DMA',
			'number' => '212',
		  ),
		  'DO' =>  array (
			'name' => 'DOMINICAN REPUBLIC',
			'A2' => 'DO',
			'A3' => 'DOM',
			'number' => '214',
		  ),
		  'EC' =>  array (
			'name' => 'ECUADOR',
			'A2' => 'EC',
			'A3' => 'ECU',
			'number' => '218',
		  ),
		  'EG' =>  array (
			'name' => 'EGYPT',
			'A2' => 'EG',
			'A3' => 'EGY',
			'number' => '818',
		  ),
		  'SV' =>  array (
			'name' => 'EL SALVADOR',
			'A2' => 'SV',
			'A3' => 'SLV',
			'number' => '222',
		  ),
		  'GQ' =>  array (
			'name' => 'EQUATORIAL GUINEA',
			'A2' => 'GQ',
			'A3' => 'GNQ',
			'number' => '226',
		  ),
		  'ER' =>  array (
			'name' => 'ERITREA',
			'A2' => 'ER',
			'A3' => 'ERI',
			'number' => '232',
		  ),
		  'EE' =>  array (
			'name' => 'ESTONIA',
			'A2' => 'EE',
			'A3' => 'EST',
			'number' => '233',
		  ),
		  'ET' =>  array (
			'name' => 'ETHIOPIA',
			'A2' => 'ET',
			'A3' => 'ETH',
			'number' => '231',
		  ),
		  'FK' =>  array (
			'name' => 'FALKLAND ISLANDS (MALVINAS)',
			'A2' => 'FK',
			'A3' => 'FLK',
			'number' => '238',
		  ),
		  'FO' =>  array (
			'name' => 'FAROE ISLANDS',
			'A2' => 'FO',
			'A3' => 'FRO',
			'number' => '234',
		  ),
		  'FJ' =>  array (
			'name' => 'FIJI',
			'A2' => 'FJ',
			'A3' => 'FJI',
			'number' => '242',
		  ),
		  'FI' =>  array (
			'name' => 'FINLAND',
			'A2' => 'FI',
			'A3' => 'FIN',
			'number' => '246',
		  ),
		  'FR' =>  array (
			'name' => 'FRANCE',
			'A2' => 'FR',
			'A3' => 'FRA',
			'number' => '250',
		  ),
		  'GF' =>  array (
			'name' => 'FRENCH GUIANA',
			'A2' => 'GF',
			'A3' => 'GUF',
			'number' => '254',
		  ),
		  'PF' =>  array (
			'name' => 'FRENCH POLYNESIA',
			'A2' => 'PF',
			'A3' => 'PYF',
			'number' => '258',
		  ),
		  'TF' =>  array (
			'name' => 'FRENCH SOUTHERN TERRITORIES',
			'A2' => 'TF',
			'A3' => 'ATF',
			'number' => '260',
		  ),
		  'GA' =>  array (
			'name' => 'GABON',
			'A2' => 'GA',
			'A3' => 'GAB',
			'number' => '266',
		  ),
		  'GM' =>  array (
			'name' => 'GAMBIA',
			'A2' => 'GM',
			'A3' => 'GMB',
			'number' => '270',
		  ),
		  'GE' =>  array (
			'name' => 'GEORGIA',
			'A2' => 'GE',
			'A3' => 'GEO',
			'number' => '268',
		  ),
		  'DE' =>  array (
			'name' => 'GERMANY',
			'A2' => 'DE',
			'A3' => 'DEU',
			'number' => '276',
		  ),
		  'GH' =>  array (
			'name' => 'GHANA',
			'A2' => 'GH',
			'A3' => 'GHA',
			'number' => '288',
		  ),
		  'GI' =>  array (
			'name' => 'GIBRALTAR',
			'A2' => 'GI',
			'A3' => 'GIB',
			'number' => '292',
		  ),
		  'GR' =>  array (
			'name' => 'GREECE',
			'A2' => 'GR',
			'A3' => 'GRC',
			'number' => '300',
		  ),
		  'GL' =>  array (
			'name' => 'GREENLAND',
			'A2' => 'GL',
			'A3' => 'GRL',
			'number' => '304',
		  ),
		  'GD' =>  array (
			'name' => 'GRENADA',
			'A2' => 'GD',
			'A3' => 'GRD',
			'number' => '308',
		  ),
		  'GP' =>  array (
			'name' => 'GUADELOUPE',
			'A2' => 'GP',
			'A3' => 'GLP',
			'number' => '312',
		  ),
		  'GU' =>  array (
			'name' => 'GUAM',
			'A2' => 'GU',
			'A3' => 'GUM',
			'number' => '316',
		  ),
		  'GT' =>  array (
			'name' => 'GUATEMALA',
			'A2' => 'GT',
			'A3' => 'GTM',
			'number' => '320',
		  ),
		  'GG
		' =>  array (
			'name' => 'GUERNSEY',
			'A2' => 'GG
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'GN' =>  array (
			'name' => 'GUINEA',
			'A2' => 'GN',
			'A3' => 'GIN',
			'number' => '324',
		  ),
		  'GW' =>  array (
			'name' => 'GUINEA-BISSAU',
			'A2' => 'GW',
			'A3' => 'GNB',
			'number' => '624',
		  ),
		  'GY' =>  array (
			'name' => 'GUYANA',
			'A2' => 'GY',
			'A3' => 'GUY',
			'number' => '328',
		  ),
		  'HT' =>  array (
			'name' => 'HAITI',
			'A2' => 'HT',
			'A3' => 'HTI',
			'number' => '332',
		  ),
		  'HM' =>  array (
			'name' => 'HEARD ISLAND AND MC DONALD ISLANDS',
			'A2' => 'HM',
			'A3' => 'HMD',
			'number' => '334',
		  ),
		  'VA
		' =>  array (
			'name' => 'HOLY SEE (VATICAN CITY STATE)',
			'A2' => 'VA
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'HN' =>  array (
			'name' => 'HONDURAS',
			'A2' => 'HN',
			'A3' => 'HND',
			'number' => '340',
		  ),
		  'HK' =>  array (
			'name' => 'HONG KONG',
			'A2' => 'HK',
			'A3' => 'HKG',
			'number' => '344',
		  ),
		  'HU' =>  array (
			'name' => 'HUNGARY',
			'A2' => 'HU',
			'A3' => 'HUN',
			'number' => '348',
		  ),
		  'IS' =>  array (
			'name' => 'ICELAND',
			'A2' => 'IS',
			'A3' => 'ISL',
			'number' => '352',
		  ),
		  'IN' =>  array (
			'name' => 'INDIA',
			'A2' => 'IN',
			'A3' => 'IND',
			'number' => '356',
		  ),
		  'ID' =>  array (
			'name' => 'INDONESIA',
			'A2' => 'ID',
			'A3' => 'IDN',
			'number' => '360',
		  ),
		  'IR' =>  array (
			'name' => 'IRAN (ISLAMIC REPUBLIC OF)',
			'A2' => 'IR',
			'A3' => 'IRN',
			'number' => '364',
		  ),
		  'IQ' =>  array (
			'name' => 'IRAQ',
			'A2' => 'IQ',
			'A3' => 'IRQ',
			'number' => '368',
		  ),
		  'IE' =>  array (
			'name' => 'IRELAND',
			'A2' => 'IE',
			'A3' => 'IRL',
			'number' => '372',
		  ),
		  'IM
		' =>  array (
			'name' => 'ISLE OF MAN',
			'A2' => 'IM
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'IL' =>  array (
			'name' => 'ISRAEL',
			'A2' => 'IL',
			'A3' => 'ISR',
			'number' => '376',
		  ),
		  'IT' =>  array (
			'name' => 'ITALY',
			'A2' => 'IT',
			'A3' => 'ITA',
			'number' => '380',
		  ),
		  'JM' =>  array (
			'name' => 'JAMAICA',
			'A2' => 'JM',
			'A3' => 'JAM',
			'number' => '388',
		  ),
		  'JP' =>  array (
			'name' => 'JAPAN',
			'A2' => 'JP',
			'A3' => 'JPN',
			'number' => '392',
		  ),
		  'JE
		' =>  array (
			'name' => 'JERSEY',
			'A2' => 'JE
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'JO' =>  array (
			'name' => 'JORDAN',
			'A2' => 'JO',
			'A3' => 'JOR',
			'number' => '400',
		  ),
		  'KZ' =>  array (
			'name' => 'KAZAKHSTAN',
			'A2' => 'KZ',
			'A3' => 'KAZ',
			'number' => '398',
		  ),
		  'KE' =>  array (
			'name' => 'KENYA',
			'A2' => 'KE',
			'A3' => 'KEN',
			'number' => '404',
		  ),
		  'KI' =>  array (
			'name' => 'KIRIBATI',
			'A2' => 'KI',
			'A3' => 'KIR',
			'number' => '296',
		  ),
		  'KP' =>  array (
			'name' => 'KOREA, DEMOCRATIC PEOPLE\'S REPUBLIC OF',
			'A2' => 'KP',
			'A3' => 'PRK',
			'number' => '408',
		  ),
		  'KR' =>  array (
			'name' => 'KOREA, REPUBLIC OF',
			'A2' => 'KR',
			'A3' => 'KOR',
			'number' => '410',
		  ),
		  'KW' =>  array (
			'name' => 'KUWAIT',
			'A2' => 'KW',
			'A3' => 'KWT',
			'number' => '414',
		  ),
		  'KG' =>  array (
			'name' => 'KYRGYZSTAN',
			'A2' => 'KG',
			'A3' => 'KGZ',
			'number' => '417',
		  ),
		  'LA' =>  array (
			'name' => 'LAO PEOPLE\'S DEMOCRATIC REPUBLIC',
			'A2' => 'LA',
			'A3' => 'LAO',
			'number' => '418',
		  ),
		  'LV' =>  array (
			'name' => 'LATVIA',
			'A2' => 'LV',
			'A3' => 'LVA',
			'number' => '428',
		  ),
		  'LB' =>  array (
			'name' => 'LEBANON',
			'A2' => 'LB',
			'A3' => 'LBN',
			'number' => '422',
		  ),
		  'LS' =>  array (
			'name' => 'LESOTHO',
			'A2' => 'LS',
			'A3' => 'LSO',
			'number' => '426',
		  ),
		  'LR' =>  array (
			'name' => 'LIBERIA',
			'A2' => 'LR',
			'A3' => 'LBR',
			'number' => '430',
		  ),
		  'LY' =>  array (
			'name' => 'LIBYAN ARAB JAMAHIRIYA',
			'A2' => 'LY',
			'A3' => 'LBY',
			'number' => '434',
		  ),
		  'LI' =>  array (
			'name' => 'LIECHTENSTEIN',
			'A2' => 'LI',
			'A3' => 'LIE',
			'number' => '438',
		  ),
		  'LT' =>  array (
			'name' => 'LITHUANIA',
			'A2' => 'LT',
			'A3' => 'LTU',
			'number' => '440',
		  ),
		  'LU' =>  array (
			'name' => 'LUXEMBOURG',
			'A2' => 'LU',
			'A3' => 'LUX',
			'number' => '442',
		  ),
		  'MO' =>  array (
			'name' => 'MACAO',
			'A2' => 'MO',
			'A3' => 'MAC',
			'number' => '446',
		  ),
		  'MK' =>  array (
			'name' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF',
			'A2' => 'MK',
			'A3' => 'MKD',
			'number' => '807',
		  ),
		  'MG' =>  array (
			'name' => 'MADAGASCAR',
			'A2' => 'MG',
			'A3' => 'MDG',
			'number' => '450',
		  ),
		  'MW' =>  array (
			'name' => 'MALAWI',
			'A2' => 'MW',
			'A3' => 'MWI',
			'number' => '454',
		  ),
		  'MY' =>  array (
			'name' => 'MALAYSIA',
			'A2' => 'MY',
			'A3' => 'MYS',
			'number' => '458',
		  ),
		  'MV' =>  array (
			'name' => 'MALDIVES',
			'A2' => 'MV',
			'A3' => 'MDV',
			'number' => '462',
		  ),
		  'ML' =>  array (
			'name' => 'MALI',
			'A2' => 'ML',
			'A3' => 'MLI',
			'number' => '466',
		  ),
		  'MT' =>  array (
			'name' => 'MALTA',
			'A2' => 'MT',
			'A3' => 'MLT',
			'number' => '470',
		  ),
		  'MH' =>  array (
			'name' => 'MARSHALL ISLANDS',
			'A2' => 'MH',
			'A3' => 'MHL',
			'number' => '584',
		  ),
		  'MQ' =>  array (
			'name' => 'MARTINIQUE',
			'A2' => 'MQ',
			'A3' => 'MTQ',
			'number' => '474',
		  ),
		  'MR' =>  array (
			'name' => 'MAURITANIA',
			'A2' => 'MR',
			'A3' => 'MRT',
			'number' => '478',
		  ),
		  'MU' =>  array (
			'name' => 'MAURITIUS',
			'A2' => 'MU',
			'A3' => 'MUS',
			'number' => '480',
		  ),
		  'YT' =>  array (
			'name' => 'MAYOTTE',
			'A2' => 'YT',
			'A3' => 'MYT',
			'number' => '175',
		  ),
		  'MX' =>  array (
			'name' => 'MEXICO',
			'A2' => 'MX',
			'A3' => 'MEX',
			'number' => '484',
		  ),
		  'FM' =>  array (
			'name' => 'MICRONESIA, FEDERATED STATES OF',
			'A2' => 'FM',
			'A3' => 'FSM',
			'number' => '583',
		  ),
		  'MD' =>  array (
			'name' => 'MOLDOVA, REPUBLIC OF',
			'A2' => 'MD',
			'A3' => 'MDA',
			'number' => '498',
		  ),
		  'MC' =>  array (
			'name' => 'MONACO',
			'A2' => 'MC',
			'A3' => 'MCO',
			'number' => '492',
		  ),
		  'MN' =>  array (
			'name' => 'MONGOLIA',
			'A2' => 'MN',
			'A3' => 'MNG',
			'number' => '496',
		  ),
		  'ME
		' =>  array (
			'name' => 'MONTENEGRO',
			'A2' => 'ME
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'MS' =>  array (
			'name' => 'MONTSERRAT',
			'A2' => 'MS',
			'A3' => 'MSR',
			'number' => '500',
		  ),
		  'MA' =>  array (
			'name' => 'MOROCCO',
			'A2' => 'MA',
			'A3' => 'MAR',
			'number' => '504',
		  ),
		  'MZ' =>  array (
			'name' => 'MOZAMBIQUE',
			'A2' => 'MZ',
			'A3' => 'MOZ',
			'number' => '508',
		  ),
		  'MM' =>  array (
			'name' => 'MYANMAR',
			'A2' => 'MM',
			'A3' => 'MMR',
			'number' => '104',
		  ),
		  'NA' =>  array (
			'name' => 'NAMIBIA',
			'A2' => 'NA',
			'A3' => 'NAM',
			'number' => '516',
		  ),
		  'NR' =>  array (
			'name' => 'NAURU',
			'A2' => 'NR',
			'A3' => 'NRU',
			'number' => '520',
		  ),
		  'NP' =>  array (
			'name' => 'NEPAL',
			'A2' => 'NP',
			'A3' => 'NPL',
			'number' => '524',
		  ),
		  'NL' =>  array (
			'name' => 'NETHERLANDS',
			'A2' => 'NL',
			'A3' => 'NLD',
			'number' => '528',
		  ),
		  'AN' =>  array (
			'name' => 'NETHERLANDS ANTILLES',
			'A2' => 'AN',
			'A3' => 'ANT',
			'number' => '530',
		  ),
		  'NC' =>  array (
			'name' => 'NEW CALEDONIA',
			'A2' => 'NC',
			'A3' => 'NCL',
			'number' => '540',
		  ),
		  'NZ' =>  array (
			'name' => 'NEW ZEALAND',
			'A2' => 'NZ',
			'A3' => 'NZL',
			'number' => '554',
		  ),
		  'NI' =>  array (
			'name' => 'NICARAGUA',
			'A2' => 'NI',
			'A3' => 'NIC',
			'number' => '558',
		  ),
		  'NE' =>  array (
			'name' => 'NIGER',
			'A2' => 'NE',
			'A3' => 'NER',
			'number' => '562',
		  ),
		  'NG' =>  array (
			'name' => 'NIGERIA',
			'A2' => 'NG',
			'A3' => 'NGA',
			'number' => '566',
		  ),
		  'NU' =>  array (
			'name' => 'NIUE',
			'A2' => 'NU',
			'A3' => 'NIU',
			'number' => '570',
		  ),
		  'NF' =>  array (
			'name' => 'NORFOLK ISLAND',
			'A2' => 'NF',
			'A3' => 'NFK',
			'number' => '574',
		  ),
		  'MP' =>  array (
			'name' => 'NORTHERN MARIANA ISLANDS',
			'A2' => 'MP',
			'A3' => 'MNP',
			'number' => '580',
		  ),
		  'NO' =>  array (
			'name' => 'NORWAY',
			'A2' => 'NO',
			'A3' => 'NOR',
			'number' => '578',
		  ),
		  'OM' =>  array (
			'name' => 'OMAN',
			'A2' => 'OM',
			'A3' => 'OMN',
			'number' => '512',
		  ),
		  'PK' =>  array (
			'name' => 'PAKISTAN',
			'A2' => 'PK',
			'A3' => 'PAK',
			'number' => '586',
		  ),
		  'PW' =>  array (
			'name' => 'PALAU',
			'A2' => 'PW',
			'A3' => 'PLW',
			'number' => '585',
		  ),
		  'PS
		' =>  array (
			'name' => 'PALESTINIAN TERRITORY, OCCUPIED',
			'A2' => 'PS
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'PA' =>  array (
			'name' => 'PANAMA',
			'A2' => 'PA',
			'A3' => 'PAN',
			'number' => '591',
		  ),
		  'PG' =>  array (
			'name' => 'PAPUA NEW GUINEA',
			'A2' => 'PG',
			'A3' => 'PNG',
			'number' => '598',
		  ),
		  'PY' =>  array (
			'name' => 'PARAGUAY',
			'A2' => 'PY',
			'A3' => 'PRY',
			'number' => '600',
		  ),
		  'PE' =>  array (
			'name' => 'PERU',
			'A2' => 'PE',
			'A3' => 'PER',
			'number' => '604',
		  ),
		  'PH' =>  array (
			'name' => 'PHILIPPINES',
			'A2' => 'PH',
			'A3' => 'PHL',
			'number' => '608',
		  ),
		  'PN' =>  array (
			'name' => 'PITCAIRN',
			'A2' => 'PN',
			'A3' => 'PCN',
			'number' => '612',
		  ),
		  'PL' =>  array (
			'name' => 'POLAND',
			'A2' => 'PL',
			'A3' => 'POL',
			'number' => '616',
		  ),
		  'PT' =>  array (
			'name' => 'PORTUGAL',
			'A2' => 'PT',
			'A3' => 'PRT',
			'number' => '620',
		  ),
		  'PR' =>  array (
			'name' => 'PUERTO RICO',
			'A2' => 'PR',
			'A3' => 'PRI',
			'number' => '630',
		  ),
		  'QA' =>  array (
			'name' => 'QATAR',
			'A2' => 'QA',
			'A3' => 'QAT',
			'number' => '634',
		  ),
		  'RE' =>  array (
			'name' => 'REUNION',
			'A2' => 'RE',
			'A3' => 'REU',
			'number' => '638',
		  ),
		  'RO' =>  array (
			'name' => 'ROMANIA',
			'A2' => 'RO',
			'A3' => 'ROM',
			'number' => '642',
		  ),
		  'RU' =>  array (
			'name' => 'RUSSIAN FEDERATION',
			'A2' => 'RU',
			'A3' => 'RUS',
			'number' => '643',
		  ),
		  'RW' =>  array (
			'name' => 'RWANDA',
			'A2' => 'RW',
			'A3' => 'RWA',
			'number' => '646',
		  ),
		  'EH
		' =>  array (
			'name' => 'SAHARA OCCIDENTAL',
			'A2' => 'EH
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'BL
		' =>  array (
			'name' => 'SAINT BARTHELEMY',
			'A2' => 'BL
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'SH' =>  array (
			'name' => 'SAINT HELENA',
			'A2' => 'SH',
			'A3' => 'SHN',
			'number' => '654
		',
		  ),
		  'KN' =>  array (
			'name' => 'SAINT KITTS AND NEVIS',
			'A2' => 'KN',
			'A3' => 'KNA',
			'number' => '659',
		  ),
		  'LC' =>  array (
			'name' => 'SAINT LUCIA',
			'A2' => 'LC',
			'A3' => 'LCA',
			'number' => '662',
		  ),
		  'PM' =>  array (
			'name' => 'SAINT PIERRE AND MIQUELON',
			'A2' => 'PM',
			'A3' => 'SPM',
			'number' => '666
		',
		  ),
		  'VC' =>  array (
			'name' => 'SAINT VINCENT AND THE GRENADINES',
			'A2' => 'VC',
			'A3' => 'VCT',
			'number' => '670',
		  ),
		  'WS' =>  array (
			'name' => 'SAMOA',
			'A2' => 'WS',
			'A3' => 'WSM',
			'number' => '882',
		  ),
		  'SM' =>  array (
			'name' => 'SAN MARINO',
			'A2' => 'SM',
			'A3' => 'SMR',
			'number' => '674',
		  ),
		  'ST' =>  array (
			'name' => 'SAO TOME AND PRINCIPE',
			'A2' => 'ST',
			'A3' => 'STP',
			'number' => '678',
		  ),
		  'SA' =>  array (
			'name' => 'SAUDI ARABIA',
			'A2' => 'SA',
			'A3' => 'SAU',
			'number' => '682',
		  ),
		  'SN' =>  array (
			'name' => 'SENEGAL',
			'A2' => 'SN',
			'A3' => 'SEN',
			'number' => '686',
		  ),
		  'RS
		' =>  array (
			'name' => 'SERBIA',
			'A2' => 'RS
		',
			'A3' => NULL,
			'number' => NULL,
		  ),
		  'SC' =>  array (
			'name' => 'SEYCHELLES',
			'A2' => 'SC',
			'A3' => 'SYC',
			'number' => '690',
		  ),
		  'SL' =>  array (
			'name' => 'SIERRA LEONE',
			'A2' => 'SL',
			'A3' => 'SLE',
			'number' => '694',
		  ),
		  'SG' =>  array (
			'name' => 'SINGAPORE',
			'A2' => 'SG',
			'A3' => 'SGP',
			'number' => '702',
		  ),
		  'SK' =>  array (
			'name' => 'SLOVAKIA',
			'A2' => 'SK',
			'A3' => 'SVK',
			'number' => '703',
		  ),
		  'SI' =>  array (
			'name' => 'SLOVENIA',
			'A2' => 'SI',
			'A3' => 'SVN',
			'number' => '705',
		  ),
		  'SB' =>  array (
			'name' => 'SOLOMON ISLANDS',
			'A2' => 'SB',
			'A3' => 'SLB',
			'number' => '090',
		  ),
		  'SO' =>  array (
			'name' => 'SOMALIA',
			'A2' => 'SO',
			'A3' => 'SOM',
			'number' => '706',
		  ),
		  'ZA' =>  array (
			'name' => 'SOUTH AFRICA',
			'A2' => 'ZA',
			'A3' => 'ZAF',
			'number' => '710',
		  ),
		  'SGS' =>  array (
			'name' => 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS    GS',
			'A2' => 'SGS',
			'A3' => '239',
			'number' => NULL,
		  ),
		  'ES' =>  array (
			'name' => 'SPAIN',
			'A2' => 'ES',
			'A3' => 'ESP',
			'number' => '724',
		  ),
		  'LK' =>  array (
			'name' => 'SRI LANKA',
			'A2' => 'LK',
			'A3' => 'LKA',
			'number' => '144',
		  ),
		  'SD' =>  array (
			'name' => 'SUDAN',
			'A2' => 'SD',
			'A3' => 'SDN',
			'number' => '736',
		  ),
		  'SR' =>  array (
			'name' => 'SURINAME',
			'A2' => 'SR',
			'A3' => 'SUR',
			'number' => '740',
		  ),
		  'SJ' =>  array (
			'name' => 'SVALBARD AND JAN MAYEN ISLANDS',
			'A2' => 'SJ',
			'A3' => 'SJM',
			'number' => '744',
		  ),
		  'SZ' =>  array (
			'name' => 'SWAZILAND',
			'A2' => 'SZ',
			'A3' => 'SWZ',
			'number' => '748',
		  ),
		  'SE' =>  array (
			'name' => 'SWEDEN',
			'A2' => 'SE',
			'A3' => 'SWE',
			'number' => '752',
		  ),
		  'CH' =>  array (
			'name' => 'SWITZERLAND',
			'A2' => 'CH',
			'A3' => 'CHE',
			'number' => '756',
		  ),
		  'SY' =>  array (
			'name' => 'SYRIAN ARAB REPUBLIC',
			'A2' => 'SY',
			'A3' => 'SYR',
			'number' => '760',
		  ),
		  'TW' =>  array (
			'name' => 'TAIWAN, PROVINCE OF CHINA',
			'A2' => 'TW',
			'A3' => 'TWN',
			'number' => '158',
		  ),
		  'TJ' =>  array (
			'name' => 'TAJIKISTAN',
			'A2' => 'TJ',
			'A3' => 'TJK',
			'number' => '762',
		  ),
		  'TZ' =>  array (
			'name' => 'TANZANIA, UNITED REPUBLIC OF',
			'A2' => 'TZ',
			'A3' => 'TZA',
			'number' => '834',
		  ),
		  'TH' =>  array (
			'name' => 'THAILAND',
			'A2' => 'TH',
			'A3' => 'THA',
			'number' => '764',
		  ),
		  'TG' =>  array (
			'name' => 'TOGO',
			'A2' => 'TG',
			'A3' => 'TGO',
			'number' => '768',
		  ),
		  'TK' =>  array (
			'name' => 'TOKELAU',
			'A2' => 'TK',
			'A3' => 'TKL',
			'number' => '772',
		  ),
		  'TO' =>  array (
			'name' => 'TONGA',
			'A2' => 'TO',
			'A3' => 'TON',
			'number' => '776',
		  ),
		  'TT' =>  array (
			'name' => 'TRINIDAD AND TOBAGO',
			'A2' => 'TT',
			'A3' => 'TTO',
			'number' => '780',
		  ),
		  'TN' =>  array (
			'name' => 'TUNISIA',
			'A2' => 'TN',
			'A3' => 'TUN',
			'number' => '788',
		  ),
		  'TR' =>  array (
			'name' => 'TURKEY',
			'A2' => 'TR',
			'A3' => 'TUR',
			'number' => '792',
		  ),
		  'TM' =>  array (
			'name' => 'TURKMENISTAN',
			'A2' => 'TM',
			'A3' => 'TKM',
			'number' => '795',
		  ),
		  'TC' =>  array (
			'name' => 'TURKS AND CAICOS ISLANDS',
			'A2' => 'TC',
			'A3' => 'TCA',
			'number' => '796',
		  ),
		  'TV' =>  array (
			'name' => 'TUVALU',
			'A2' => 'TV',
			'A3' => 'TUV',
			'number' => '798',
		  ),
		  'UG' =>  array (
			'name' => 'UGANDA',
			'A2' => 'UG',
			'A3' => 'UGA',
			'number' => '800',
		  ),
		  'UA' =>  array (
			'name' => 'UKRAINE',
			'A2' => 'UA',
			'A3' => 'UKR',
			'number' => '804',
		  ),
		  'AE' =>  array (
			'name' => 'UNITED ARAB EMIRATES',
			'A2' => 'AE',
			'A3' => 'ARE',
			'number' => '784',
		  ),
		  'GB' =>  array (
			'name' => 'UNITED KINGDOM',
			'A2' => 'GB',
			'A3' => 'GBR',
			'number' => '826',
		  ),
		  'US' =>  array (
			'name' => 'UNITED STATES',
			'A2' => 'US',
			'A3' => 'USA',
			'number' => '840',
		  ),
		  'UM' =>  array (
			'name' => 'UNITED STATES MINOR OUTLYING ISLANDS',
			'A2' => 'UM',
			'A3' => 'UMI',
			'number' => '581',
		  ),
		  'UY' =>  array (
			'name' => 'URUGUAY',
			'A2' => 'UY',
			'A3' => 'URY',
			'number' => '858',
		  ),
		  'UZ' =>  array (
			'name' => 'UZBEKISTAN',
			'A2' => 'UZ',
			'A3' => 'UZB',
			'number' => '860',
		  ),
		  'VU' =>  array (
			'name' => 'VANUATU',
			'A2' => 'VU',
			'A3' => 'VUT',
			'number' => '548',
		  ),
		  'VA' =>  array (
			'name' => 'VATICAN CITY STATE (HOLY SEE)',
			'A2' => 'VA',
			'A3' => 'VAT',
			'number' => '336',
		  ),
		  'VE' =>  array (
			'name' => 'VENEZUELA',
			'A2' => 'VE',
			'A3' => 'VEN',
			'number' => '862',
		  ),
		  'VN' =>  array (
			'name' => 'VIET NAM',
			'A2' => 'VN',
			'A3' => 'VNM',
			'number' => '704',
		  ),
		  'VG' =>  array (
			'name' => 'VIRGIN ISLANDS (BRITISH)',
			'A2' => 'VG',
			'A3' => 'VGB',
			'number' => '092',
		  ),
		  'VI' =>  array (
			'name' => 'VIRGIN ISLANDS (U.S.)',
			'A2' => 'VI',
			'A3' => 'VIR',
			'number' => '850',
		  ),
		  'WF' =>  array (
			'name' => 'WALLIS AND FUTUNA ISLANDS',
			'A2' => 'WF',
			'A3' => 'WLF',
			'number' => '876',
		  ),
		  'EH' =>  array (
			'name' => 'WESTERN SAHARA',
			'A2' => 'EH',
			'A3' => 'ESH',
			'number' => '732',
		  ),
		  'YE' =>  array (
			'name' => 'YEMEN',
			'A2' => 'YE',
			'A3' => 'YEM',
			'number' => '887',
		  ),
		  'ZM' =>  array (
			'name' => 'ZAMBIA',
			'A2' => 'ZM',
			'A3' => 'ZMB',
			'number' => '894',
		  ),
		  'ZW' =>  array (
			'name' => 'ZIMBABWE',
			'A2' => 'ZW',
			'A3' => 'ZWE',
			'number' => '716',
		  ),
		);
	
	static function set($lg){
		self::$language = $lg;
		self::$locales_root = control::$CWD.'langs';
		self::$domain = 'messages';
		self::$locale = strtolower(self::$language).'_'.strtoupper(self::$language);
	}
	static function update_cache(){
		$filename = self::$locales_root.'/'.self::$locale.'/LC_MESSAGES/'.self::$domain.'.mo';
		if(!is_file($filename)) return;
		$mtime = filemtime($filename);
		$filename_new = self::$locales_root.'/'.self::$locale.'/LC_MESSAGES/'.self::$domain.'_'.$mtime.'.mo';
		if(!file_exists($filename_new)){
			$dir = scandir(dirname($filename));
			foreach($dir as $file){
				if(in_array($file, array('.','..', self::$domain.'.po', self::$domain.'.mo'))) continue;
				unlink(dirname($filename).DS.$file);
			}
			copy($filename,$filename_new);
		}
		self::$domain = self::$domain.'_'.$mtime;
	}
	static function handle(){
		date_default_timezone_set('Europe/Paris');
		
		$lang = self::$locale;
		$all_locales = explode("\n",shell_exec('locale -a'));
		if(!in_array($lang,$all_locales)){
			/* allow gettext to access local translate dir even if that local type is not available on system */
			putenv("LANGUAGE=$lang");
			putenv("LC_ALL=$lang");
			if(in_array($lang.'.utf8',$all_locales))
				$lang .= '.utf8';
		}		
		T_setlocale(LC_ALL,$lang);
		//setlocale(LC_TIME, $lang);
		T_bind_textdomain_codeset(self::$domain, "UTF-8");
		T_bindtextdomain(self::$domain,self::$locales_root);
		T_textdomain(self::$domain);
		
		 //bind_textdomain_codeset(self::$domain, "UTF-8");
		 //bindtextdomain(self::$domain,self::$locales_root);
		 //textdomain(self::$domain);
	}
}
