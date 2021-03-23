-- Adminer 4.6.2 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `admin_action_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `actioner_id` int(11) DEFAULT NULL,
  `action` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C979128586DFF2` (`user_info_id`),
  KEY `IDX_C979128166D1F9C` (`project_id`),
  KEY `IDX_C979128E402B000` (`actioner_id`),
  CONSTRAINT `FK_C979128166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_C979128586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_C979128E402B000` FOREIGN KEY (`actioner_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `app_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_type` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `learn_more_link` longtext COLLATE utf8_unicode_ci,
  `expire_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `app_message_read` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `app_message_id` int(11) DEFAULT NULL,
  `read_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F34CC03F586DFF2` (`user_info_id`),
  KEY `IDX_F34CC03F561B855` (`app_message_id`),
  CONSTRAINT `FK_F34CC03F561B855` FOREIGN KEY (`app_message_id`) REFERENCES `app_message` (`id`),
  CONSTRAINT `FK_F34CC03F586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_category_id` int(11) DEFAULT NULL,
  `spotlight_user_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `short_desc` longtext COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `seo_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `seo_desc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `read_count` int(11) NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_23A0E6688C5F785` (`article_category_id`),
  KEY `IDX_23A0E6645F4E028` (`spotlight_user_id`),
  KEY `IDX_23A0E66F675F31B` (`author_id`),
  CONSTRAINT `FK_23A0E6645F4E028` FOREIGN KEY (`spotlight_user_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_23A0E6688C5F785` FOREIGN KEY (`article_category_id`) REFERENCES `article_category` (`id`),
  CONSTRAINT `FK_23A0E66F675F31B` FOREIGN KEY (`author_id`) REFERENCES `author` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `article_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL,
  `display` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `article_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `author` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `bio` longtext COLLATE utf8_unicode_ci NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `counter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `date` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `count` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C1229478586DFF2` (`user_info_id`),
  CONSTRAINT `FK_C1229478586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `counter` (`id`, `user_info_id`, `date`, `type`, `count`, `created_at`, `updated_at`) VALUES
(1,	16,	'2018-05',	'connect_request',	'3',	'2018-05-10 13:54:46',	'2018-05-10 13:55:00');

CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `sort` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `countries` (`id`, `title`, `code`, `sort`) VALUES
(1,	'Afghanistan',	'AF',	10),
(2,	'Åland Islands',	'AX',	10),
(3,	'Albania',	'AL',	10),
(4,	'Algeria',	'DZ',	10),
(5,	'American Samoa',	'AS',	10),
(6,	'Andorra',	'AD',	10),
(7,	'Angola',	'AO',	10),
(8,	'Anguilla',	'AI',	10),
(9,	'Antarctica',	'AQ',	10),
(10,	'Antigua and Barbuda',	'AG',	10),
(11,	'Argentina',	'AR',	10),
(12,	'Armenia',	'AM',	10),
(13,	'Aruba',	'AW',	10),
(14,	'Australia',	'AU',	5),
(15,	'Austria',	'AT',	10),
(16,	'Azerbaijan',	'AZ',	10),
(17,	'Bahrain',	'BH',	10),
(18,	'Bahamas',	'BS',	10),
(19,	'Bangladesh',	'BD',	10),
(20,	'Barbados',	'BB',	10),
(21,	'Belarus',	'BY',	10),
(22,	'Belgium',	'BE',	10),
(23,	'Belize',	'BZ',	10),
(24,	'Benin',	'BJ',	10),
(25,	'Bermuda',	'BM',	10),
(26,	'Bhutan',	'BT',	10),
(27,	'Bolivia',	'BO',	10),
(28,	'Bonaire',	'BQ',	10),
(29,	'Bosnia and Herzegovina',	'BA',	10),
(30,	'Botswana',	'BW',	10),
(31,	'Bouvet Island',	'BV',	10),
(32,	'Brazil',	'BR',	10),
(33,	'British Indian Ocean Territory',	'IO',	10),
(34,	'Brunei Darussalam',	'BN',	10),
(35,	'Bulgaria',	'BG',	10),
(36,	'Burkina Faso',	'BF',	10),
(37,	'Burundi',	'BI',	10),
(38,	'Cambodia',	'KH',	10),
(39,	'Cameroon',	'CM',	10),
(40,	'Canada',	'CA',	5),
(41,	'Cape Verde',	'CV',	10),
(42,	'Cayman Islands',	'KY',	10),
(43,	'Central African Republic',	'CF',	10),
(44,	'Chad',	'TD',	10),
(45,	'Chile',	'CL',	10),
(46,	'China',	'CN',	10),
(47,	'Christmas Island',	'CX',	10),
(48,	'Cocos (Keeling) Islands',	'CC',	10),
(49,	'Colombia',	'CO',	10),
(50,	'Comoros',	'KM',	10),
(51,	'Congo',	'CG',	10),
(53,	'Cook Islands',	'CK',	10),
(54,	'Costa Rica',	'CR',	10),
(55,	'Côte d\'Ivoire',	'CI',	10),
(56,	'Croatia',	'HR',	10),
(57,	'Cuba',	'CU',	10),
(58,	'Curaçao',	'CW',	10),
(59,	'Cyprus',	'CY',	10),
(60,	'Czech Republic',	'CZ',	10),
(61,	'Denmark',	'DK',	10),
(62,	'Djibouti',	'DJ',	10),
(63,	'Dominica',	'DM',	10),
(64,	'Dominican Republic',	'DO',	10),
(65,	'Ecuador',	'EC',	10),
(66,	'Egypt',	'EG',	10),
(67,	'El Salvador',	'SV',	10),
(68,	'Equatorial Guinea',	'GQ',	10),
(69,	'Eritrea',	'ER',	10),
(70,	'Estonia',	'EE',	10),
(71,	'Ethiopia',	'ET',	10),
(72,	'Falkland Islands (Malvinas)',	'FK',	10),
(73,	'Faroe Islands',	'FO',	10),
(74,	'Fiji',	'FJ',	10),
(75,	'Finland',	'FI',	10),
(76,	'France',	'FR',	10),
(77,	'French Guiana',	'GF',	10),
(78,	'French Polynesia',	'PF',	10),
(79,	'French Southern Territories',	'TF',	10),
(80,	'Gabon',	'GA',	10),
(81,	'Gambia',	'GM',	10),
(82,	'Georgia',	'GE',	10),
(83,	'Germany',	'DE',	10),
(84,	'Ghana',	'GH',	10),
(85,	'Gibraltar',	'GI',	10),
(86,	'Greece',	'GR',	10),
(87,	'Greenland',	'GL',	10),
(88,	'Grenada',	'GD',	10),
(89,	'Guadeloupe',	'GP',	10),
(90,	'Guam',	'GU',	10),
(91,	'Guatemala',	'GT',	10),
(92,	'Guernsey',	'GG',	10),
(93,	'Guinea',	'GN',	10),
(94,	'Guinea-Bissau',	'GW',	10),
(95,	'Guyana',	'GY',	10),
(96,	'Haiti',	'HT',	10),
(97,	'Heard Island and McDonald Islands',	'HM',	10),
(98,	'Holy See (Vatican City State)',	'VA',	10),
(99,	'Honduras',	'HN',	10),
(100,	'Hong Kong',	'HK',	10),
(101,	'Hungary',	'HU',	10),
(102,	'Iceland',	'IS',	10),
(103,	'India',	'IN',	10),
(104,	'Indonesia',	'ID',	10),
(105,	'Iran',	'IR',	10),
(106,	'Iraq',	'IQ',	10),
(107,	'Ireland',	'IE',	10),
(108,	'Isle of Man',	'IM',	10),
(109,	'Israel',	'IL',	10),
(110,	'Italy',	'IT',	10),
(111,	'Jamaica',	'JM',	10),
(112,	'Japan',	'JP',	10),
(113,	'Jersey',	'JE',	10),
(114,	'Jordan',	'JO',	10),
(115,	'Kazakhstan',	'KZ',	10),
(116,	'Kenya',	'KE',	10),
(117,	'Kiribati',	'KI',	10),
(118,	'North Korea',	'KP',	10),
(119,	'Korea',	'KR',	10),
(120,	'Kuwait',	'KW',	10),
(121,	'Kyrgyzstan',	'KG',	10),
(122,	'Lao People\'s Democratic Republic',	'LA',	10),
(123,	'Latvia',	'LV',	10),
(124,	'Lebanon',	'LB',	10),
(125,	'Lesotho',	'LS',	10),
(126,	'Liberia',	'LR',	10),
(127,	'Libya',	'LY',	10),
(128,	'Liechtenstein',	'LI',	10),
(129,	'Lithuania',	'LT',	10),
(130,	'Luxembourg',	'LU',	10),
(131,	'Macao',	'MO',	10),
(132,	'Macedonia',	'MK',	10),
(133,	'Madagascar',	'MG',	10),
(134,	'Malawi',	'MW',	10),
(135,	'Malaysia',	'MY',	10),
(136,	'Maldives',	'MV',	10),
(137,	'Mali',	'ML',	10),
(138,	'Malta',	'MT',	10),
(139,	'Marshall Islands',	'MH',	10),
(140,	'Martinique',	'MQ',	10),
(141,	'Mauritania',	'MR',	10),
(142,	'Mauritius',	'MU',	10),
(143,	'Mayotte',	'YT',	10),
(144,	'Mexico',	'MX',	10),
(145,	'Micronesia',	'FM',	10),
(146,	'Moldova',	'MD',	10),
(147,	'Monaco',	'MC',	10),
(148,	'Mongolia',	'MN',	10),
(149,	'Montenegro',	'ME',	10),
(150,	'Montserrat',	'MS',	10),
(151,	'Morocco',	'MA',	10),
(152,	'Mozambique',	'MZ',	10),
(153,	'Myanmar',	'MM',	10),
(154,	'Namibia',	'NA',	10),
(155,	'Nauru',	'NR',	10),
(156,	'Nepal',	'NP',	10),
(157,	'Netherlands',	'NL',	10),
(158,	'New Caledonia',	'NC',	10),
(159,	'New Zealand',	'NZ',	10),
(160,	'Nicaragua',	'NI',	10),
(161,	'Niger',	'NE',	10),
(162,	'Nigeria',	'NG',	10),
(163,	'Niue',	'NU',	10),
(164,	'Norfolk Island',	'NF',	10),
(165,	'Northern Mariana Islands',	'MP',	10),
(166,	'Norway',	'NO',	10),
(167,	'Oman',	'OM',	10),
(168,	'Pakistan',	'PK',	10),
(169,	'Palau',	'PW',	10),
(170,	'Palestine',	'PS',	10),
(171,	'Panama',	'PA',	10),
(172,	'Papua New Guinea',	'PG',	10),
(173,	'Paraguay',	'PY',	10),
(174,	'Peru',	'PE',	10),
(175,	'Philippines',	'PH',	10),
(176,	'Pitcairn',	'PN',	10),
(177,	'Poland',	'PL',	10),
(178,	'Portugal',	'PT',	10),
(179,	'Puerto Rico',	'PR',	10),
(180,	'Qatar',	'QA',	10),
(181,	'Réunion',	'RE',	10),
(182,	'Romania',	'RO',	10),
(183,	'Russian Federation',	'RU',	10),
(184,	'Rwanda',	'RW',	10),
(185,	'Saint Barthélemy',	'BL',	10),
(186,	'Saint Helena',	'SH',	10),
(187,	'Saint Kitts and Nevis',	'KN',	10),
(188,	'Saint Lucia',	'LC',	10),
(189,	'Saint Martin (French part)',	'MF',	10),
(190,	'Saint Pierre and Miquelon',	'PM',	10),
(191,	'Saint Vincent and the Grenadines',	'VC',	10),
(192,	'Samoa',	'WS',	10),
(193,	'San Marino',	'SM',	10),
(194,	'Sao Tome and Principe',	'ST',	10),
(195,	'Saudi Arabia',	'SA',	10),
(196,	'Senegal',	'SN',	10),
(197,	'Serbia',	'RS',	10),
(198,	'Seychelles',	'SC',	10),
(199,	'Sierra Leone',	'SL',	10),
(200,	'Singapore',	'SG',	10),
(201,	'Sint Maarten (Dutch part)',	'SX',	10),
(202,	'Slovakia',	'SK',	10),
(203,	'Slovenia',	'SI',	10),
(204,	'Solomon Islands',	'SB',	10),
(205,	'Somalia',	'SO',	10),
(206,	'South Africa',	'ZA',	10),
(207,	'South Georgia and the South Sandwich Islands',	'GS',	10),
(208,	'South Sudan',	'SS',	10),
(209,	'Spain',	'ES',	10),
(210,	'Sri Lanka',	'LK',	10),
(211,	'Sudan',	'SD',	10),
(212,	'Suriname',	'SR',	10),
(213,	'Svalbard and Jan Mayen',	'SJ',	10),
(214,	'Swaziland',	'SZ',	10),
(215,	'Sweden',	'SE',	10),
(216,	'Switzerland',	'CH',	10),
(217,	'Syrian Arab Republic',	'SY',	10),
(218,	'Taiwan',	'TW',	10),
(219,	'Tajikistan',	'TJ',	10),
(220,	'Tanzania',	'TZ',	10),
(221,	'Thailand',	'TH',	10),
(222,	'Timor-Leste',	'TL',	10),
(223,	'Togo',	'TG',	10),
(224,	'Tokelau',	'TK',	10),
(225,	'Tonga',	'TO',	10),
(226,	'Trinidad and Tobago',	'TT',	10),
(227,	'Tunisia',	'TN',	10),
(228,	'Turkey',	'TR',	10),
(229,	'Turkmenistan',	'TM',	10),
(230,	'Turks and Caicos Islands',	'TC',	10),
(231,	'Tuvalu',	'TV',	10),
(232,	'Uganda',	'UG',	10),
(233,	'Ukraine',	'UA',	10),
(234,	'United Arab Emirates',	'AE',	10),
(235,	'United Kingdom',	'UK',	5),
(236,	'United States',	'US',	5),
(237,	'United States Minor Outlying Islands',	'UM',	10),
(238,	'Uruguay',	'UY',	10),
(239,	'Uzbekistan',	'UZ',	10),
(240,	'Vanuatu',	'VU',	10),
(241,	'Venezuela',	'VE',	10),
(242,	'Viet Nam',	'VN',	10),
(243,	'British Virgin Islands',	'VG',	10),
(244,	'U.S. Virgin Islands',	'VI',	10),
(245,	'Wallis and Futuna',	'WF',	10),
(246,	'Western Sahara',	'EH',	10),
(247,	'Yemen',	'YE',	10),
(248,	'Zambia',	'ZM',	10),
(249,	'Zimbabwe',	'ZW',	10);

CREATE TABLE `email_change_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `unique_key` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_136F31FFE7927C74` (`email`),
  UNIQUE KEY `UNIQ_136F31FF586DFF2` (`user_info_id`),
  CONSTRAINT `FK_136F31FF586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `engine_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `engine_product_id` int(11) DEFAULT NULL,
  `uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `fee` int(11) NOT NULL,
  `notes` longtext COLLATE utf8_unicode_ci,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D84147D3586DFF2` (`user_info_id`),
  KEY `IDX_D84147D3A57FE1BB` (`engine_product_id`),
  CONSTRAINT `FK_D84147D3586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_D84147D3A57FE1BB` FOREIGN KEY (`engine_product_id`) REFERENCES `engine_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `engine_order_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `engine_order_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_56DC07C8586DFF2` (`user_info_id`),
  KEY `IDX_56DC07C8576DE645` (`engine_order_id`),
  CONSTRAINT `FK_56DC07C8576DE645` FOREIGN KEY (`engine_order_id`) REFERENCES `engine_order` (`id`),
  CONSTRAINT `FK_56DC07C8586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `engine_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `pro_only` tinyint(1) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `entry_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_bid_id` int(11) DEFAULT NULL,
  `ip_addr` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `browser` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FE32FD77586DFF2` (`user_info_id`),
  KEY `IDX_FE32FD775A3C8DF2` (`project_bid_id`),
  CONSTRAINT `FK_FE32FD77586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_FE32FD775A3C8DF2` FOREIGN KEY (`project_bid_id`) REFERENCES `project_bid` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `genre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `genre` (`id`, `title`) VALUES
(1,	'Electronica'),
(2,	'Progressive House'),
(3,	'Trance'),
(4,	'Tech'),
(5,	'Techno'),
(6,	'Electro'),
(7,	'Drum N Bass'),
(8,	'House'),
(9,	'Dubstep'),
(10,	'Chill Out'),
(11,	'Hardcore'),
(12,	'Indie Dance'),
(13,	'Nu Disco'),
(14,	'Trap'),
(15,	'Funk'),
(16,	'RnB'),
(17,	'Hip Hop'),
(18,	'Rap'),
(19,	'Rock'),
(20,	'Heavey Metal'),
(21,	'Prog Rock'),
(22,	'Country / Western'),
(23,	'Indie Rock'),
(24,	'Punk'),
(25,	'Pop'),
(26,	'Blues'),
(27,	'Soul'),
(28,	'Opera'),
(29,	'Reggae'),
(30,	'Jazz'),
(31,	'Hard Rock'),
(32,	'Folk'),
(33,	'Classical'),
(34,	'Latin'),
(35,	'Breaks');

CREATE TABLE `language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `language` (`id`, `title`) VALUES
(1,	'English'),
(2,	'Spanish'),
(3,	'French'),
(4,	'Dutch'),
(5,	'Italian'),
(6,	'Mandarin'),
(7,	'Japanese'),
(8,	'South Korean');

CREATE TABLE `language_project` (
  `language_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  PRIMARY KEY (`language_id`,`project_id`),
  KEY `IDX_8B7E07BA82F1BAF4` (`language_id`),
  KEY `IDX_8B7E07BA166D1F9C` (`project_id`),
  CONSTRAINT `FK_8B7E07BA166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8B7E07BA82F1BAF4` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `mag_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `uid` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `unsubscribe_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6843FF586DFF2` (`user_info_id`),
  CONSTRAINT `FK_6843FF586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `marketplace_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `approved_by_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `status_reason` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `additional_info` longtext COLLATE utf8_unicode_ci,
  `item_type` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `bpm` int(11) DEFAULT NULL,
  `audio_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `is_auction` tinyint(1) DEFAULT NULL,
  `has_assets` tinyint(1) DEFAULT NULL,
  `bids_due` date DEFAULT NULL,
  `num_bids` int(11) NOT NULL,
  `buyout_price` int(11) DEFAULT NULL,
  `reserve_price` int(11) DEFAULT NULL,
  `royalty_master` int(11) DEFAULT NULL,
  `royalty_publishing` int(11) DEFAULT NULL,
  `royalty_mechanical` int(11) DEFAULT NULL,
  `royalty_performance` int(11) DEFAULT NULL,
  `gender` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `approved` tinyint(1) NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D600F78586DFF2` (`user_info_id`),
  KEY `IDX_D600F782D234F6A` (`approved_by_id`),
  KEY `title_idx` (`title`),
  KEY `published_at_idx` (`published_at`),
  CONSTRAINT `FK_D600F782D234F6A` FOREIGN KEY (`approved_by_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_D600F78586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `marketplace_item` (`id`, `user_info_id`, `approved_by_id`, `uuid`, `title`, `status`, `status_reason`, `additional_info`, `item_type`, `bpm`, `audio_key`, `is_auction`, `has_assets`, `bids_due`, `num_bids`, `buyout_price`, `reserve_price`, `royalty_master`, `royalty_publishing`, `royalty_mechanical`, `royalty_performance`, `gender`, `published_at`, `approved`, `approved_at`, `updated_at`, `created_at`) VALUES
(1,	16,	NULL,	'5af18ecb3b7d1',	'dfgdfg',	'draft',	NULL,	'dsfhsdfhsdfhdfhdf',	'vocal',	111,	'123',	0,	0,	NULL,	0,	1,	NULL,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	'2018-05-08 18:49:31'),
(2,	16,	NULL,	'5af18ed6c9572',	'dfgdfg',	'draft',	NULL,	'dsfhsdfhsdfhdfhdf',	'vocal',	111,	'123',	0,	0,	NULL,	0,	1,	NULL,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	'2018-05-08 18:49:42'),
(3,	16,	NULL,	'5af18ee41024d',	'dfgdfg',	'draft',	NULL,	'dsfhsdfhsdfhdfhdf',	'vocal',	111,	'123',	0,	0,	NULL,	0,	1,	NULL,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	'2018-05-08 18:49:56');

CREATE TABLE `marketplace_item_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `marketplace_item_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `preview_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DE48CAED586DFF2` (`user_info_id`),
  KEY `IDX_DE48CAEDF6898142` (`marketplace_item_id`),
  CONSTRAINT `FK_DE48CAED586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_DE48CAEDF6898142` FOREIGN KEY (`marketplace_item_id`) REFERENCES `marketplace_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `marketplace_item_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `marketplace_item_id` int(11) DEFAULT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flag` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `wave_generated` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C49AA624586DFF2` (`user_info_id`),
  KEY `IDX_C49AA624F6898142` (`marketplace_item_id`),
  CONSTRAINT `FK_C49AA624586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_C49AA624F6898142` FOREIGN KEY (`marketplace_item_id`) REFERENCES `marketplace_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `marketplace_item_audio` (`id`, `user_info_id`, `marketplace_item_id`, `title`, `path`, `duration`, `duration_string`, `slug`, `flag`, `wave_generated`, `created_at`, `updated_at`) VALUES
(1,	16,	3,	'Poop Fart FAILS.mp3',	'9c06dfa46aeaef1cf2875873b9ba5a8ee0067458.mp3',	328124,	'5:28',	'poop-fart-failsmp3-06480d7c',	'F',	0,	'2018-05-08 18:49:56',	NULL);

CREATE TABLE `marketplace_item_genre` (
  `marketplace_item_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`marketplace_item_id`,`genre_id`),
  KEY `IDX_5FB7A349F6898142` (`marketplace_item_id`),
  KEY `IDX_5FB7A3494296D31F` (`genre_id`),
  CONSTRAINT `FK_5FB7A3494296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`),
  CONSTRAINT `FK_5FB7A349F6898142` FOREIGN KEY (`marketplace_item_id`) REFERENCES `marketplace_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `marketplace_item_genre` (`marketplace_item_id`, `genre_id`) VALUES
(1,	35),
(2,	35),
(3,	35);

CREATE TABLE `message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_thread_id` int(11) DEFAULT NULL,
  `user_info_id` int(11) DEFAULT NULL,
  `to_user_info_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `user_ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B6BD307F8829462F` (`message_thread_id`),
  KEY `IDX_B6BD307F586DFF2` (`user_info_id`),
  KEY `IDX_B6BD307FD1137C2E` (`to_user_info_id`),
  CONSTRAINT `FK_B6BD307F586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_B6BD307F8829462F` FOREIGN KEY (`message_thread_id`) REFERENCES `message_thread` (`id`),
  CONSTRAINT `FK_B6BD307FD1137C2E` FOREIGN KEY (`to_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `message_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `message_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filesize` int(11) NOT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_250AADC9586DFF2` (`user_info_id`),
  KEY `IDX_250AADC9166D1F9C` (`project_id`),
  KEY `IDX_250AADC9537A1329` (`message_id`),
  CONSTRAINT `FK_250AADC9166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_250AADC9537A1329` FOREIGN KEY (`message_id`) REFERENCES `message` (`id`),
  CONSTRAINT `FK_250AADC9586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `message_thread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employer_id` int(11) DEFAULT NULL,
  `bidder_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `num_employer_unread` int(11) NOT NULL,
  `employer_last_read` datetime DEFAULT NULL,
  `num_bidder_unread` int(11) NOT NULL,
  `bidder_last_read` datetime DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `last_message_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_607D18C41CD9E7A` (`employer_id`),
  KEY `IDX_607D18CBE40AFAE` (`bidder_id`),
  KEY `IDX_607D18C166D1F9C` (`project_id`),
  CONSTRAINT `FK_607D18C166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_607D18C41CD9E7A` FOREIGN KEY (`employer_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_607D18CBE40AFAE` FOREIGN KEY (`bidder_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `actioned_user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `user_audio_id` int(11) DEFAULT NULL,
  `notify_type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `notify_read` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF5476CA586DFF2` (`user_info_id`),
  KEY `IDX_BF5476CAABF1F0F6` (`actioned_user_info_id`),
  KEY `IDX_BF5476CA166D1F9C` (`project_id`),
  KEY `IDX_BF5476CADABCC7C7` (`user_audio_id`),
  CONSTRAINT `FK_BF5476CA166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_BF5476CA586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_BF5476CAABF1F0F6` FOREIGN KEY (`actioned_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_BF5476CADABCC7C7` FOREIGN KEY (`user_audio_id`) REFERENCES `user_audio` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `notification` (`id`, `user_info_id`, `actioned_user_info_id`, `project_id`, `user_audio_id`, `notify_type`, `data`, `created_at`, `notify_read`) VALUES
(4,	16,	32,	NULL,	NULL,	'connect_accept',	NULL,	'2018-05-10 13:55:18',	0),
(5,	16,	31,	NULL,	NULL,	'connect_accept',	NULL,	'2018-05-10 13:56:01',	0),
(6,	16,	30,	NULL,	NULL,	'connect_accept',	NULL,	'2018-05-10 13:56:28',	0);

CREATE TABLE `paypal_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txn_type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `ipn_track_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `txn_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subscr_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payer_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payment_gross` decimal(9,3) NOT NULL,
  `amount` decimal(9,3) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `raw` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `employee_user_info_id` int(11) DEFAULT NULL,
  `project_bid_id` int(11) DEFAULT NULL,
  `user_transaction_id` int(11) DEFAULT NULL,
  `hire_user_id` int(11) DEFAULT NULL,
  `project_escrow_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `awarded_at` datetime DEFAULT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `project_type` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `lyrics` longtext COLLATE utf8_unicode_ci,
  `due_date` date DEFAULT NULL,
  `bids_due` date DEFAULT NULL,
  `num_bids` int(11) NOT NULL,
  `bid_total` int(11) NOT NULL,
  `last_bid_at` datetime DEFAULT NULL,
  `gender` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `studio_access` tinyint(1) DEFAULT NULL,
  `pro_required` tinyint(1) DEFAULT NULL,
  `budget_from` int(11) DEFAULT NULL,
  `budget_to` int(11) DEFAULT NULL,
  `royalty_mechanical` tinyint(1) DEFAULT NULL,
  `royalty_performance` tinyint(1) DEFAULT NULL,
  `royalty` int(11) DEFAULT NULL,
  `looking_for` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_lat` double DEFAULT NULL,
  `location_lng` double DEFAULT NULL,
  `enable_gig_hunter` datetime DEFAULT NULL,
  `publish_type` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `to_favorites` tinyint(1) NOT NULL,
  `show_in_news` tinyint(1) NOT NULL,
  `restrict_to_preferences` tinyint(1) DEFAULT NULL,
  `highlight` tinyint(1) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL,
  `featured_at` datetime DEFAULT NULL,
  `fees` int(11) NOT NULL,
  `bpm` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `is_complete` tinyint(1) NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `employer_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `employee_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prompt_assets` tinyint(1) NOT NULL,
  `last_activity` longtext COLLATE utf8_unicode_ci,
  `employer_read_at` datetime DEFAULT NULL,
  `employee_read_at` datetime DEFAULT NULL,
  `audio_brief` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audio_brief_click` int(11) NOT NULL,
  `sfs` tinyint(1) DEFAULT NULL,
  `public_voting` tinyint(1) DEFAULT NULL,
  `user_ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2FB3D0EE4B209DC8` (`project_escrow_id`),
  KEY `IDX_2FB3D0EE586DFF2` (`user_info_id`),
  KEY `IDX_2FB3D0EE2BB3F4B2` (`employee_user_info_id`),
  KEY `IDX_2FB3D0EE5A3C8DF2` (`project_bid_id`),
  KEY `IDX_2FB3D0EE44451456` (`user_transaction_id`),
  KEY `IDX_2FB3D0EE2C5E08EB` (`hire_user_id`),
  KEY `IDX_2FB3D0EE82F1BAF4` (`language_id`),
  KEY `title_idx` (`title`),
  KEY `published_at_idx` (`published_at`),
  CONSTRAINT `FK_2FB3D0EE2BB3F4B2` FOREIGN KEY (`employee_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_2FB3D0EE2C5E08EB` FOREIGN KEY (`hire_user_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_2FB3D0EE44451456` FOREIGN KEY (`user_transaction_id`) REFERENCES `user_transaction` (`id`),
  CONSTRAINT `FK_2FB3D0EE4B209DC8` FOREIGN KEY (`project_escrow_id`) REFERENCES `project_escrow` (`id`),
  CONSTRAINT `FK_2FB3D0EE586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_2FB3D0EE5A3C8DF2` FOREIGN KEY (`project_bid_id`) REFERENCES `project_bid` (`id`),
  CONSTRAINT `FK_2FB3D0EE82F1BAF4` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `project` (`id`, `user_info_id`, `employee_user_info_id`, `project_bid_id`, `user_transaction_id`, `hire_user_id`, `project_escrow_id`, `language_id`, `uuid`, `awarded_at`, `title`, `project_type`, `description`, `lyrics`, `due_date`, `bids_due`, `num_bids`, `bid_total`, `last_bid_at`, `gender`, `studio_access`, `pro_required`, `budget_from`, `budget_to`, `royalty_mechanical`, `royalty_performance`, `royalty`, `looking_for`, `city`, `state`, `country`, `location_lat`, `location_lng`, `enable_gig_hunter`, `publish_type`, `published_at`, `to_favorites`, `show_in_news`, `restrict_to_preferences`, `highlight`, `featured`, `featured_at`, `fees`, `bpm`, `created_at`, `updated_at`, `is_active`, `is_complete`, `completed_at`, `employer_name`, `employee_name`, `prompt_assets`, `last_activity`, `employer_read_at`, `employee_read_at`, `audio_brief`, `audio_brief_click`, `sfs`, `public_voting`, `user_ip`) VALUES
(1,	16,	NULL,	NULL,	NULL,	NULL,	NULL,	1,	'5af18e3305b3d',	NULL,	'123e',	'contest',	'asdsdagfsdghsfdgsdgfsdgsdfgsdgfsdgsdgsdfgsdfgsdfgsdgfsd',	NULL,	NULL,	NULL,	0,	0,	NULL,	NULL,	1,	0,	300,	0,	1,	1,	31,	'vocalist',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'public',	NULL,	0,	1,	0,	0,	0,	NULL,	0,	'128',	'2018-05-08 18:46:59',	'2018-05-08 18:47:56',	0,	0,	NULL,	NULL,	NULL,	0,	'{}',	NULL,	NULL,	NULL,	0,	0,	0,	NULL),
(2,	16,	NULL,	NULL,	NULL,	NULL,	1,	6,	'5afa677b674c1',	NULL,	'Test contest manddarin',	'contest',	'uiojkll;kjjkluiojkll;kjjkluiojkll;kjjkluiojkll;kjjkluiojkll;kjjkl uiojkll;kjjkluiojkll;kjjkluiojkll;kjjkluiojkll;kjjkluiojkll;kjjkl',	NULL,	NULL,	'2018-05-29',	0,	0,	NULL,	NULL,	0,	0,	300,	300,	1,	1,	71,	'vocalist',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'public',	'2018-05-15 11:52:16',	0,	1,	0,	0,	0,	NULL,	0,	'120',	'2018-05-15 11:52:11',	'2018-05-15 12:32:30',	1,	0,	NULL,	NULL,	NULL,	0,	'{}',	'2018-05-15 12:32:30',	NULL,	NULL,	0,	0,	0,	'127.0.0.1');

CREATE TABLE `project_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `actioned_user_info_id` int(11) DEFAULT NULL,
  `activity_type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `activity_read` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_913A8281586DFF2` (`user_info_id`),
  KEY `IDX_913A8281166D1F9C` (`project_id`),
  KEY `IDX_913A8281ABF1F0F6` (`actioned_user_info_id`),
  CONSTRAINT `FK_913A8281166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_913A8281586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_913A8281ABF1F0F6` FOREIGN KEY (`actioned_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `preview_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_11FA53C2586DFF2` (`user_info_id`),
  KEY `IDX_11FA53C2166D1F9C` (`project_id`),
  CONSTRAINT `FK_11FA53C2166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_11FA53C2586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flag` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `wave_generated` tinyint(1) NOT NULL,
  `download_count` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B283F0B586DFF2` (`user_info_id`),
  KEY `IDX_B283F0B166D1F9C` (`project_id`),
  CONSTRAINT `FK_B283F0B166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_B283F0B586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `project_audio` (`id`, `user_info_id`, `project_id`, `title`, `path`, `duration`, `duration_string`, `slug`, `flag`, `wave_generated`, `download_count`, `created_at`, `updated_at`) VALUES
(1,	16,	1,	'Poop Fart FAILS.mp3',	'ea3b7a914e2db606d37358c68f7cef758e7e899c.mp3',	328124,	'5:28',	'poop-fart-failsmp3-b4f38bd9',	'F',	0,	0,	'2018-05-08 18:46:59',	NULL),
(2,	16,	2,	'Poop Fart FAILS.mp3',	'6d4b5197050e6851fc9b747e3a986c32b43a9d4d.mp3',	328124,	'5:28',	'poop-fart-failsmp3-d63d4e16',	'F',	0,	0,	'2018-05-15 11:52:11',	NULL);

CREATE TABLE `project_audio_download` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_audio_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1EF79F24586DFF2` (`user_info_id`),
  KEY `IDX_1EF79F2477FA81C` (`project_audio_id`),
  CONSTRAINT `FK_1EF79F24586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_1EF79F2477FA81C` FOREIGN KEY (`project_audio_id`) REFERENCES `project_audio` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_bid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  `shortlist` tinyint(1) NOT NULL,
  `flag` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flag_comment` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `payment_percent_taken` int(11) DEFAULT NULL,
  `wave_generated` tinyint(1) NOT NULL,
  `vote_count` int(11) NOT NULL,
  `user_ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D8896910586DFF2` (`user_info_id`),
  KEY `IDX_D8896910166D1F9C` (`project_id`),
  CONSTRAINT `FK_D8896910166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_D8896910586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `project_audio_id` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_26A5E09166D1F9C` (`project_id`),
  KEY `IDX_26A5E0977FA81C` (`project_audio_id`),
  KEY `IDX_26A5E0978CED90B` (`from_id`),
  CONSTRAINT `FK_26A5E09166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_26A5E0977FA81C` FOREIGN KEY (`project_audio_id`) REFERENCES `project_audio` (`id`),
  CONSTRAINT `FK_26A5E0978CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_contract` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D4C1A382586DFF2` (`user_info_id`),
  KEY `IDX_D4C1A382166D1F9C` (`project_id`),
  CONSTRAINT `FK_D4C1A382166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_D4C1A382586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_dispute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `from_user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `reason` longtext COLLATE utf8_unicode_ci NOT NULL,
  `accepted` tinyint(1) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AA8C5C62586DFF2` (`user_info_id`),
  KEY `IDX_AA8C5C6238C00514` (`from_user_info_id`),
  KEY `IDX_AA8C5C62166D1F9C` (`project_id`),
  CONSTRAINT `FK_AA8C5C62166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_AA8C5C6238C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_AA8C5C62586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_escrow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_bid_id` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `fee` int(11) NOT NULL,
  `contractor_fee` int(11) NOT NULL,
  `released_date` datetime DEFAULT NULL,
  `refunded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_12CF393B586DFF2` (`user_info_id`),
  KEY `IDX_12CF393B5A3C8DF2` (`project_bid_id`),
  CONSTRAINT `FK_12CF393B586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_12CF393B5A3C8DF2` FOREIGN KEY (`project_bid_id`) REFERENCES `project_bid` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `project_escrow` (`id`, `user_info_id`, `project_bid_id`, `amount`, `fee`, `contractor_fee`, `released_date`, `refunded`, `created_at`, `updated_at`) VALUES
(1,	16,	NULL,	30000,	0,	0,	NULL,	0,	'2018-05-15 11:52:16',	NULL);

CREATE TABLE `project_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `from_user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `object_type` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `feed_read` tinyint(1) NOT NULL,
  `notified` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1AD18CB3586DFF2` (`user_info_id`),
  KEY `IDX_1AD18CB338C00514` (`from_user_info_id`),
  KEY `IDX_1AD18CB3166D1F9C` (`project_id`),
  CONSTRAINT `FK_1AD18CB3166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_1AD18CB338C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_1AD18CB3586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `project_comment_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filesize` int(11) NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B50EFE08586DFF2` (`user_info_id`),
  KEY `IDX_B50EFE08166D1F9C` (`project_id`),
  KEY `IDX_B50EFE08E0CF0621` (`project_comment_id`),
  CONSTRAINT `FK_B50EFE08166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_B50EFE08586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_B50EFE08E0CF0621` FOREIGN KEY (`project_comment_id`) REFERENCES `project_comment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_genre` (
  `project_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`genre_id`),
  KEY `IDX_90053A66166D1F9C` (`project_id`),
  KEY `IDX_90053A664296D31F` (`genre_id`),
  CONSTRAINT `FK_90053A66166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_90053A664296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `project_genre` (`project_id`, `genre_id`) VALUES
(1,	26),
(2,	33);

CREATE TABLE `project_invite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D046FB9D586DFF2` (`user_info_id`),
  KEY `IDX_D046FB9D166D1F9C` (`project_id`),
  CONSTRAINT `FK_D046FB9D166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_D046FB9D586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_lyrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `lyrics` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2C7E872C586DFF2` (`user_info_id`),
  KEY `IDX_2C7E872C166D1F9C` (`project_id`),
  CONSTRAINT `FK_2C7E872C166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_2C7E872C586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `public` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_20A33C1A166D1F9C` (`project_id`),
  KEY `IDX_20A33C1A78CED90B` (`from_id`),
  CONSTRAINT `FK_20A33C1A166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_20A33C1A78CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_upgrade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `upgrade` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2178787F586DFF2` (`user_info_id`),
  KEY `IDX_2178787F166D1F9C` (`project_id`),
  CONSTRAINT `FK_2178787F166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_2178787F586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `role` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `project_owner` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B4021E51586DFF2` (`user_info_id`),
  CONSTRAINT `FK_B4021E51586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_vocal_characteristics` (
  `project_id` int(11) NOT NULL,
  `vocal_characteristic_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`vocal_characteristic_id`),
  KEY `IDX_4DC9276A166D1F9C` (`project_id`),
  KEY `IDX_4DC9276AAB3A6FD2` (`vocal_characteristic_id`),
  CONSTRAINT `FK_4DC9276A166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_4DC9276AAB3A6FD2` FOREIGN KEY (`vocal_characteristic_id`) REFERENCES `vocal_characteristic` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `project_vocal_styles` (
  `project_id` int(11) NOT NULL,
  `vocal_style_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`vocal_style_id`),
  KEY `IDX_7DA0398E166D1F9C` (`project_id`),
  KEY `IDX_7DA0398E9DDCAC1B` (`vocal_style_id`),
  CONSTRAINT `FK_7DA0398E166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_7DA0398E9DDCAC1B` FOREIGN KEY (`vocal_style_id`) REFERENCES `vocal_style` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `reset_pass_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `unique_key` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3D7F1191586DFF2` (`user_info_id`),
  CONSTRAINT `FK_3D7F1191586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `search_term` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `num_results` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B4F0DBA7586DFF2` (`user_info_id`),
  CONSTRAINT `FK_B4F0DBA7586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `statistics_type` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `users` int(11) NOT NULL,
  `vocalists` int(11) NOT NULL,
  `producers` int(11) NOT NULL,
  `gigs` int(11) NOT NULL,
  `published_gigs` int(11) NOT NULL,
  `public_published_gigs` int(11) NOT NULL,
  `private_published_gigs` int(11) NOT NULL,
  `awarded_gigs` int(11) NOT NULL,
  `public_awarded_gigs` int(11) NOT NULL,
  `private_awarded_gigs` int(11) NOT NULL,
  `completed_gigs` int(11) NOT NULL,
  `public_completed_gigs` int(11) NOT NULL,
  `private_completed_gigs` int(11) NOT NULL,
  `revenue` int(11) NOT NULL,
  `bids` int(11) NOT NULL,
  `messages` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type_idx` (`statistics_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `stripe_charge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` int(11) NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `subscription_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `price` int(11) NOT NULL,
  `user_audio_limit` int(11) DEFAULT NULL,
  `project_percent_added` int(11) NOT NULL,
  `payment_percent_taken` int(11) NOT NULL,
  `project_private_fee` int(11) NOT NULL,
  `project_highlight_fee` int(11) NOT NULL,
  `project_feature_fee` int(11) NOT NULL,
  `project_announce_fee` int(11) NOT NULL,
  `connect_month_limit` int(11) NOT NULL DEFAULT '5',
  `message_month_limit` int(11) DEFAULT '5',
  `static_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `unique_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `hidden` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `subscription_plan` (`id`, `title`, `description`, `price`, `user_audio_limit`, `project_percent_added`, `payment_percent_taken`, `project_private_fee`, `project_highlight_fee`, `project_feature_fee`, `project_announce_fee`, `connect_month_limit`, `message_month_limit`, `static_key`, `unique_key`, `updated_at`, `hidden`, `created_at`) VALUES
(1,	'Free Membership',	'Free Membership',	0,	2,	3,	10,	5,	10,	10,	10,	5,	5,	'FREE',	'8e6d3d171e6b3bbbfb7428502e11628f',	NULL,	0,	'2018-05-08 17:57:34'),
(2,	'dfg',	'dsfhdhj',	1,	NULL,	2,	3,	5,	5,	4,	7,	5,	5,	'er6g45',	'45gw46',	NULL,	0,	'2018-05-24 15:43:33'),
(3,	'dfg',	'dsfhdhj',	1,	NULL,	2,	3,	5,	5,	4,	7,	5,	5,	'er6g45',	'45gw46',	NULL,	0,	'2018-05-24 15:43:33'),
(4,	'dfg',	'dsfhdhj',	1,	NULL,	2,	3,	5,	5,	4,	7,	5,	5,	'er6g45',	'45gw46',	NULL,	0,	'2018-05-24 15:43:33');

CREATE TABLE `user_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `sc_user_track_id` int(11) DEFAULT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `default_audio` tinyint(1) NOT NULL,
  `sc_id` int(11) DEFAULT NULL,
  `sc_synced` tinyint(1) NOT NULL,
  `sc_sync_start` datetime DEFAULT NULL,
  `sc_sync_finished` datetime DEFAULT NULL,
  `sc_permalink_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sc_stream_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sc_download_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sc_raw` longtext COLLATE utf8_unicode_ci,
  `play_count` int(11) NOT NULL,
  `total_likes` int(11) NOT NULL,
  `sc_upload_queued` int(11) NOT NULL,
  `sc_upload_result` int(11) DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `wave_generated` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FABFCDCD586DFF2` (`user_info_id`),
  KEY `IDX_FABFCDCD89E2828E` (`sc_user_track_id`),
  CONSTRAINT `FK_FABFCDCD586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_FABFCDCD89E2828E` FOREIGN KEY (`sc_user_track_id`) REFERENCES `user_audio` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_block` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `block_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_61D96C7A586DFF2` (`user_info_id`),
  KEY `IDX_61D96C7ADD4D276B` (`block_user_id`),
  CONSTRAINT `FK_61D96C7A586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_61D96C7ADD4D276B` FOREIGN KEY (`block_user_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_cancel_sub` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `reason` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7ACC2060586DFF2` (`user_info_id`),
  CONSTRAINT `FK_7ACC2060586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_connect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_id` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `engaged` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2CC2E71530354A65` (`to_id`),
  KEY `IDX_2CC2E71578CED90B` (`from_id`),
  CONSTRAINT `FK_2CC2E71530354A65` FOREIGN KEY (`to_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_2CC2E71578CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `user_connect` (`id`, `to_id`, `from_id`, `engaged`, `created_at`) VALUES
(2,	16,	32,	1,	'2018-05-10 13:55:18'),
(3,	32,	16,	0,	'2018-05-10 13:55:18'),
(4,	16,	31,	1,	'2018-05-10 13:56:01'),
(5,	31,	16,	0,	'2018-05-10 13:56:01'),
(6,	16,	30,	1,	'2018-05-10 13:56:28'),
(7,	30,	16,	0,	'2018-05-10 13:56:28');

CREATE TABLE `user_connect_invite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_id` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `message` longtext COLLATE utf8_unicode_ci,
  `connected_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7947C43A30354A65` (`to_id`),
  KEY `IDX_7947C43A78CED90B` (`from_id`),
  CONSTRAINT `FK_7947C43A30354A65` FOREIGN KEY (`to_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_7947C43A78CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `user_connect_invite` (`id`, `to_id`, `from_id`, `status`, `message`, `connected_at`, `created_at`, `updated_at`) VALUES
(2,	30,	16,	1,	'I\'d like to add you to my professional network on Vocalizr.',	'2018-05-10 13:56:28',	'2018-05-10 13:54:46',	'2018-05-10 13:56:28'),
(3,	31,	16,	1,	'I\'d like to add you to my professional network on Vocalizr.',	'2018-05-10 13:56:01',	'2018-05-10 13:54:53',	'2018-05-10 13:56:01'),
(4,	32,	16,	1,	'I\'d like to add you to my professional network on Vocalizr.',	'2018-05-10 13:55:18',	'2018-05-10 13:55:00',	'2018-05-10 13:55:18');

CREATE TABLE `user_favorite` (
  `user_info_id` int(11) NOT NULL,
  `favorite_user_info_id` int(11) NOT NULL,
  PRIMARY KEY (`user_info_id`,`favorite_user_info_id`),
  KEY `IDX_88486AD9586DFF2` (`user_info_id`),
  KEY `IDX_88486AD9EA90DD9D` (`favorite_user_info_id`),
  CONSTRAINT `FK_88486AD9586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_88486AD9EA90DD9D` FOREIGN KEY (`favorite_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_follow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `follow_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D665F4D586DFF2` (`user_info_id`),
  KEY `IDX_D665F4DF99B8B25` (`follow_user_id`),
  CONSTRAINT `FK_D665F4D586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_D665F4DF99B8B25` FOREIGN KEY (`follow_user_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_genre` (
  `user_info_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`user_info_id`,`genre_id`),
  KEY `IDX_6192C8A0586DFF2` (`user_info_id`),
  KEY `IDX_6192C8A04296D31F` (`genre_id`),
  CONSTRAINT `FK_6192C8A04296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`),
  CONSTRAINT `FK_6192C8A0586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_stat_id` int(11) DEFAULT NULL,
  `soundcloud_id` int(11) DEFAULT NULL,
  `soundcloud_access_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `soundcloud_set_id` int(11) DEFAULT NULL,
  `soundcloud_username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `salt` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `avatar` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `profile` longtext COLLATE utf8_unicode_ci,
  `gender` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_lat` double DEFAULT NULL,
  `location_lng` double DEFAULT NULL,
  `studio_access` tinyint(1) DEFAULT NULL,
  `microphone` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `vocalist_fee` int(11) DEFAULT NULL,
  `producer_fee` int(11) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `unique_str` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `wallet` int(11) NOT NULL,
  `date_registered` datetime NOT NULL,
  `completed_profile` datetime DEFAULT NULL,
  `is_producer` tinyint(1) NOT NULL,
  `is_vocalist` tinyint(1) NOT NULL,
  `is_songwriter` tinyint(1) NOT NULL,
  `email_confirmed` tinyint(1) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `referral_code` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rating` decimal(9,2) NOT NULL,
  `rated_count` int(11) NOT NULL,
  `rating_total` int(11) NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `unread_project_activity` tinyint(1) NOT NULL,
  `unseen_project_invitation` tinyint(1) NOT NULL,
  `display_name` varchar(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_activated` datetime DEFAULT NULL,
  `soundcloud_register` tinyint(1) NOT NULL,
  `num_unread_messages` int(11) NOT NULL,
  `is_admin` tinyint(1) NOT NULL,
  `is_certified` tinyint(1) NOT NULL,
  `num_notifications` int(11) NOT NULL,
  `subscription_plan_id` int(11) DEFAULT NULL,
  `stripe_cust_id` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `connect_count` int(11) NOT NULL,
  `user_spotify_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `login_ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `register_ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B1087D9E515D3101` (`user_stat_id`),
  KEY `email_idx` (`email`),
  KEY `date_registered_idx` (`date_registered`),
  KEY `last_login_idx` (`last_login`),
  KEY `IDX_B1087D9E9B8CE200` (`subscription_plan_id`),
  KEY `rating_idx` (`rating`,`rated_count`,`last_login`),
  CONSTRAINT `FK_B1087D9E515D3101` FOREIGN KEY (`user_stat_id`) REFERENCES `user_stat` (`id`),
  CONSTRAINT `FK_B1087D9E9B8CE200` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plan` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `user_info` (`id`, `user_stat_id`, `soundcloud_id`, `soundcloud_access_token`, `soundcloud_set_id`, `soundcloud_username`, `username`, `first_name`, `last_name`, `email`, `password`, `salt`, `avatar`, `profile`, `gender`, `state`, `country`, `city`, `location_lat`, `location_lng`, `studio_access`, `microphone`, `vocalist_fee`, `producer_fee`, `last_activity`, `last_login`, `unique_str`, `wallet`, `date_registered`, `completed_profile`, `is_producer`, `is_vocalist`, `is_songwriter`, `email_confirmed`, `is_active`, `referral_code`, `rating`, `rated_count`, `rating_total`, `path`, `unread_project_activity`, `unseen_project_invitation`, `display_name`, `date_activated`, `soundcloud_register`, `num_unread_messages`, `is_admin`, `is_certified`, `num_notifications`, `subscription_plan_id`, `stripe_cust_id`, `connect_count`, `user_spotify_id`, `login_ip`, `register_ip`) VALUES
(1,	NULL,	NULL,	NULL,	NULL,	NULL,	'LukeChable',	'Luke',	'Chable',	'luke@vocalizr.com',	'607b6f5cf25044bbd6fdcecca04e907086cb94b7',	'bf3e20840874ee1314a375b6164cade6',	NULL,	'Professional producer & engineer of 20+ years, and singer/topliner for quite a few of those.\r\nExtensive experience producing, mixing and engineering tracks for Vocalists and for my own original work. Genres include electronic, dance electronic, rock & pop, rnb.',	'm',	'Victoria',	'AU',	'Melbourne',	-37.814107,	144.96327999999994,	1,	'Neumann TLM 103',	0,	0,	'2018-05-23 01:30:47',	'2018-05-23 01:30:47',	'u53070ff4b38ca4.88163532',	0,	'2014-02-21 08:36:04',	'2014-02-22 04:09:56',	1,	1,	0,	1,	1,	NULL,	5.00,	4,	20,	'3c9bd151cc4666a48149977bd83c70b52de766d3.jpg',	0,	0,	'Luke C',	'2014-02-21 08:36:04',	0,	0,	1,	1,	0,	2,	'cus_8NcOYiLEVssGk6',	45,	'0kQZetIaDwYkwPnp2Pb4eW',	'203.213.41.62',	NULL),
(2,	NULL,	NULL,	NULL,	NULL,	NULL,	'Annelise',	'Annelise',	'Rowell',	'anneliserowell@gmail.com',	'62b4a67441c46a6ff843f882b1ce8f7685398814',	'ded838af30bd370049a981736ba8d23b',	NULL,	NULL,	'f',	'Victoria',	'AU',	'Melbourne',	-37.814107,	144.96327999999994,	1,	'Neumann TLM 103',	150000,	NULL,	'2018-03-26 13:30:51',	'2017-07-13 13:14:37',	'u53071cc6a7a5a7.27218759',	0,	'2014-02-21 09:30:46',	'2014-02-24 05:08:10',	0,	1,	0,	1,	1,	NULL,	0.00,	0,	0,	'e9155bba27fd371e43c176bb27d25c03e4aa415c.jpg',	1,	1,	NULL,	'2014-02-21 09:30:46',	0,	0,	0,	1,	27,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(3,	NULL,	NULL,	NULL,	NULL,	NULL,	'natomo',	NULL,	NULL,	'natomomusic@gmail.com',	'2b9c92a40a2e862745c4e1428053c983cc05efb9',	'678c1557c1374614a97cd7c91f55353f',	NULL,	NULL,	'm',	'Queensland',	'AU',	'Gladstone',	-23.8487083,	151.2597998,	0,	NULL,	NULL,	NULL,	'2014-02-21 09:35:49',	NULL,	'u53071d17c5aee3.72325632',	0,	'2014-02-21 09:32:07',	NULL,	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	NULL,	0,	0,	NULL,	'2014-02-21 09:32:07',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(4,	NULL,	37819174,	'1-33128-37819174-1998952452fa289',	NULL,	NULL,	'robwood',	NULL,	NULL,	'robert@vocalizr.com',	'53fcd8d413da7ad0c8fbef049a6a6d1917823747',	'1ad51d78d6cb53721072823083c9900e',	NULL,	NULL,	'm',	'Victoria',	'AU',	'Melbourne',	-37.814107,	144.96327999999994,	0,	NULL,	NULL,	NULL,	'2017-05-02 04:30:15',	'2017-05-02 04:30:15',	'u53071d2be18e56.89260366',	0,	'2014-02-21 09:32:27',	'2014-02-22 13:02:40',	0,	1,	0,	1,	1,	NULL,	0.00,	0,	0,	'ad5fa0b5a0c056c058e7008d562bdff9ae46a904.jpg',	0,	0,	NULL,	'2014-02-21 09:32:27',	0,	0,	1,	0,	0,	2,	'cus_8NL1iDDuJ0tq9o',	1,	NULL,	NULL,	NULL),
(5,	NULL,	79303,	'1-33128-79303-7d06f00cdee2298e20',	NULL,	NULL,	'SteveMay',	'Steve',	'May',	'may.steve@gmail.com',	'95f549e87d3ceac3cfb509f783724d0b9896edd0',	'3be6df8f5c3d4f5ea582361ca1735986',	NULL,	'As a Producer his original productions, remixes and edits have garnered considerable praise, been licensed to some of the biggest dance music compilations, topped ARIA Charts, and appeared in the global radio broadcast (and live sets) of Armin Van Buuren, Tiesto, Ferry Corsten, Markus Schulz, Andy Moor, Matt Darey, Perry O\'neill and Paul Van Dyk. Steve\'s tunes have received club play from Sasha, John Digweed, Carl Cox, Hernan Cattaneo, Hybrid and scores of other top tier DJs from every corner of the globe. \r\n\r\n2012 saw Steve team up with Luke Chable for various productions including Rokit, On Mesmeric Records.\r\n\r\nSteves successes hasn\'t been limited to just his productions, of course. As a DJ, he has championed a versatile and genre-bending sound across the globe headlining shows in Japan, China, Canada, Lithuania, New Zealand and Thailand. Steve has supported some of the industries biggest names including John Digweed, Andy Moor, Gareth Emery, J00f, Luke Fair, Jimmy Van M (in China) and Christopher Lawrence. Steve has also played at some of Australias biggest festivals and club nights including Future Music Festival, Gods Kitchen, Summadayze, Sunshine People, pharmacy and many more.',	'm',	'Victoria',	'AU',	'Melbourne',	-37.814107,	144.96327999999994,	0,	NULL,	NULL,	50000,	'2018-02-16 11:13:06',	'2017-07-19 22:28:35',	'u53071eb2c47f05.42751290',	0,	'2014-02-21 09:38:58',	'2014-02-22 13:00:22',	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	'76c8bf86514df8a6526145eb72c1a9c7ccc74b0a.jpg',	0,	0,	NULL,	'2014-02-21 09:38:58',	0,	0,	0,	1,	5,	NULL,	NULL,	4,	NULL,	NULL,	NULL),
(6,	NULL,	2783567,	'1-33128-2783567-b89e20196ad8300a',	NULL,	NULL,	'AngeloK',	NULL,	NULL,	'angelo.musik72@gmail.com',	'8deaec51a5af93b30df912fa592e9611308fc74f',	'de4c3ddb31105ed1bc110cf29cb4910e',	NULL,	'',	'm',	'Attica',	'GR',	'Athens',	37.9837155,	23.72930969999993,	1,	NULL,	NULL,	NULL,	'2017-09-24 02:15:34',	'2017-09-24 02:15:34',	'u53072896afdd91.08831450',	0,	'2014-02-21 10:21:10',	'2014-02-21 14:42:40',	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	'cdad949f16408895fe2c4019d90617ffb23abeec.jpg',	0,	0,	NULL,	'2014-02-21 10:21:10',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(7,	NULL,	NULL,	NULL,	NULL,	NULL,	'ninaxgive',	NULL,	NULL,	'ninaxtaylor@aol.com',	'8ac4cb6789bee616fc4774e1679a10953739f6c3',	'ff05e11ec90a7be062894d295deba38c',	NULL,	NULL,	'f',	'Victoria',	'AU',	'Melbourne',	-37.814107,	144.96327999999994,	0,	NULL,	NULL,	NULL,	'2014-12-07 05:36:23',	NULL,	'u530729061f3052.67434933',	0,	'2014-02-21 10:23:02',	NULL,	1,	1,	0,	1,	1,	NULL,	0.00,	0,	0,	NULL,	0,	1,	NULL,	'2014-02-21 10:23:02',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(8,	NULL,	NULL,	NULL,	NULL,	NULL,	'santoro909',	NULL,	NULL,	'djmarcussantoro@outlook.com',	'09f4c271a9834afb07f2ae37286ea98b49f2c0a5',	'1507a60ed34a99d06f06011dbeb11a24',	NULL,	NULL,	'm',	'Victoria',	'AU',	'Melbourne',	-37.8142155,	144.96323069999994,	0,	NULL,	NULL,	NULL,	'2014-02-21 10:25:38',	NULL,	'u5307291c5cea58.27708686',	0,	'2014-02-21 10:23:24',	'2014-02-21 10:25:38',	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	'ae16facab70d113880cd667437139cb0dd75b1d4.jpg',	0,	0,	NULL,	'2014-02-21 10:23:24',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(9,	NULL,	11908949,	'1-33128-11908949-18f8d49225dd326',	NULL,	NULL,	'jpijpers',	'Jurriaan',	'Pijpers',	'jpijpers@hotmail.com',	'22f366034da87e10ef362db57d10178ede675815',	'29f752f1f7448ee13ff2cd259c31ffae',	NULL,	NULL,	'm',	'South Holland',	'NL',	'Leiderdorp',	52.1509854,	4.528173299999935,	0,	NULL,	NULL,	NULL,	'2014-02-21 10:45:51',	NULL,	'u53072ae9f29cd1.63116786',	0,	'2014-02-21 10:31:05',	'2014-02-21 10:37:09',	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	'827466e1d940d6ba50b25c5389e26d31b6950913.jpg',	0,	0,	NULL,	'2014-02-21 10:31:05',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(10,	NULL,	NULL,	NULL,	NULL,	NULL,	'Magre',	NULL,	NULL,	'magremusic@gmail.com',	'9570ed707def3473f11503b191eac8d638afb9a7',	'77add9098148bc7c4aea4831210a30c8',	NULL,	NULL,	'm',	'New South Wales',	'AU',	'Sydney',	-33.8674869,	151.20699020000006,	0,	NULL,	NULL,	NULL,	'2014-08-21 03:16:56',	'2014-08-21 03:16:56',	'u53073133502e93.30904365',	0,	'2014-02-21 10:57:55',	NULL,	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	NULL,	0,	0,	NULL,	'2014-02-21 10:57:55',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(11,	NULL,	NULL,	NULL,	NULL,	NULL,	'alisonspong',	NULL,	NULL,	'alison@spong.com.au',	'53fe29cb6c10922b8969460a7f13aa0ca44ff903',	'b15a94ee353f3c4ba9c43954fee4daab',	NULL,	NULL,	'f',	'Victoria',	'AU',	'Melbourne',	-37.814107,	144.96327999999994,	0,	NULL,	NULL,	NULL,	'2014-08-18 00:05:48',	'2014-08-18 00:05:48',	'u5307349a74e189.06745506',	0,	'2014-02-21 11:12:26',	NULL,	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	NULL,	0,	0,	NULL,	'2014-02-21 11:12:26',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(12,	NULL,	NULL,	NULL,	NULL,	NULL,	'keithinnate',	NULL,	NULL,	'keith@baroquerecords.co.uk',	'b05b6b9aea78cee7eac71bcae4e935eb5a9ac53b',	'072a43aca2251db18db6f4ca2a25111f',	NULL,	NULL,	'm',	'England',	'GB',	'Coventry',	52.406822,	-1.519692999999961,	0,	NULL,	NULL,	NULL,	'2014-08-17 09:59:55',	'2014-08-17 09:59:55',	'u530734fececc49.21774148',	0,	'2014-02-21 11:14:06',	NULL,	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	NULL,	0,	0,	NULL,	'2014-02-21 11:14:06',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(13,	NULL,	NULL,	NULL,	NULL,	NULL,	'djtrafik',	NULL,	NULL,	'djtrafik@gmail.com',	'c26e97bd59ee6077f8048eb473d1b930bebe8474',	'11a4932c1330fd6746385b0789a7a15d',	NULL,	NULL,	'm',	'England',	'GB',	'Newcastle upon Tyne',	54.978252,	-1.6177800000000389,	0,	NULL,	NULL,	NULL,	'2014-02-21 11:41:49',	NULL,	'u53073b570098f7.86498826',	0,	'2014-02-21 11:41:10',	NULL,	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	NULL,	0,	0,	NULL,	'2014-02-21 11:41:10',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(14,	NULL,	NULL,	NULL,	NULL,	NULL,	'randomdan',	'Dan',	'Cole',	'dan@blueprintmedia.nl',	'28c999fcc6bdf84d84188ca32c6766afb4574e9a',	'c9ce7b66cb252a4f1a9cfa136f6eb297',	NULL,	NULL,	'm',	'Berlin',	'DE',	'Berlin',	52.52000659999999,	13.404953999999975,	0,	NULL,	NULL,	NULL,	'2016-04-05 00:24:37',	'2016-04-04 08:57:13',	'u53073c710b4212.70324534',	0,	'2014-02-21 11:45:53',	'2016-04-04 08:57:14',	0,	1,	0,	1,	0,	NULL,	0.00,	0,	0,	'f26c5db5c99f9d153c55f1eceb3f3009125e2fa3.jpg',	0,	1,	NULL,	'2014-02-21 11:45:53',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(15,	NULL,	NULL,	NULL,	NULL,	NULL,	'denisehorowitz',	NULL,	NULL,	'denisehorowitz@gmail.com',	'ccf6980537757ab51e55f3f21ccdd62e8ba8a042',	'020f2b45344b004d5c6245db2bea762f',	NULL,	NULL,	'f',	'Noord-Holland',	'NL',	'Amsterdam',	52.3702157,	4.895167899999933,	0,	NULL,	NULL,	NULL,	'2014-07-06 11:41:22',	'2014-07-06 11:41:22',	'u53073cea6990a7.38397355',	0,	'2014-02-21 11:47:54',	NULL,	0,	1,	0,	1,	0,	NULL,	0.00,	0,	0,	NULL,	0,	0,	NULL,	'2014-02-21 11:47:54',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(16,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'production_line2040@hotmail.com',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	'u53073dac444033.33671271',	0,	'2014-02-21 11:51:08',	NULL,	0,	0,	0,	0,	0,	NULL,	0.00,	0,	0,	NULL,	0,	0,	NULL,	NULL,	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(17,	NULL,	NULL,	NULL,	NULL,	NULL,	'Dannyboy',	'Danny',	'O\'Donnell',	'fengshuifaders@gmail.com',	'4f327b62776f82fe939149e2fd08b41d02ac35b5',	'ccadd836038e60b1ea5377444d18e162',	NULL,	'\r\n\r\n',	'm',	'New South Wales',	'AU',	'Sydney',	-33.8674869,	151.20699020000006,	1,	NULL,	NULL,	0,	'2018-01-24 09:31:59',	'2018-01-24 09:27:28',	'u53073f0b890972.81943411',	0,	'2014-02-21 11:56:59',	'2014-02-21 12:00:42',	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	'a1506bf615ee5cf01ea10b4470f94f26eaccee17.jpg',	0,	0,	NULL,	'2014-02-21 11:56:59',	0,	0,	0,	0,	0,	NULL,	NULL,	1,	NULL,	NULL,	NULL),
(18,	NULL,	NULL,	NULL,	NULL,	NULL,	'Mintee',	NULL,	NULL,	'mintee@live.com',	'929c1fcb4a7bdecf4ad53425b8fd53a6a38a2ee7',	'53468df9c2c26f3d23e47855e4236914',	NULL,	NULL,	'm',	'California',	'US',	'Los Angeles',	34.0522342,	-118.2436849,	0,	NULL,	NULL,	NULL,	'2014-02-21 12:11:43',	NULL,	'u5307419368f5d0.39328053',	0,	'2014-02-21 12:07:47',	'2014-02-21 12:11:43',	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	'debbe094f59fe2841bc48dfe542c71fdff544614.png',	0,	0,	NULL,	'2014-02-21 12:07:47',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(19,	NULL,	NULL,	NULL,	NULL,	NULL,	'Marcrow',	NULL,	NULL,	'mcalic91@hotmail.com',	'4b49f77be6b0b541b4a5623ee0dd11f8b01f5c66',	'0144d0801ba68a0d767a322f47c82868',	NULL,	NULL,	'm',	'Victoria',	'AU',	'Frankston',	-38.1413993,	145.12246389999996,	0,	NULL,	NULL,	NULL,	'2014-12-07 05:41:38',	NULL,	'u5307440b2bbd38.77530339',	0,	'2014-02-21 12:18:19',	NULL,	0,	1,	0,	1,	1,	NULL,	0.00,	0,	0,	NULL,	0,	1,	NULL,	'2014-02-21 12:18:19',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL),
(20,	NULL,	NULL,	NULL,	NULL,	NULL,	'malchia',	'Mal',	'Chia',	'mal@musio.co',	'e1d1000623133f3e5d285da9acaf83db9a3442f9',	'368c334fd8f777218ae27935e0b555a9',	NULL,	NULL,	'm',	'South Australia',	'AU',	'Adelaide',	-34.92862119999999,	138.5999594,	0,	NULL,	NULL,	NULL,	'2015-10-16 12:20:14',	'2015-10-16 12:20:14',	'u53074ad6929a42.62731866',	0,	'2014-02-21 12:47:18',	'2014-02-21 12:54:26',	1,	0,	0,	1,	1,	NULL,	0.00,	0,	0,	'8a5908abf1b5751c0a2ac75aaccd5563ea007920.jpg',	0,	0,	NULL,	'2014-02-21 12:47:18',	0,	0,	0,	0,	0,	NULL,	NULL,	0,	NULL,	NULL,	NULL);

CREATE TABLE `user_info_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) DEFAULT NULL,
  `userInfo_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6624311DE5704DA` (`userInfo_id`),
  KEY `IDX_6624311D82F1BAF4` (`language_id`),
  CONSTRAINT `FK_6624311D82F1BAF4` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`),
  CONSTRAINT `FK_6624311DE5704DA` FOREIGN KEY (`userInfo_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `user_info_languages` (`id`, `language_id`, `userInfo_id`) VALUES
(6,	2,	16);

CREATE TABLE `user_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_id` int(11) DEFAULT NULL,
  `to_id` int(11) DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EEB02E7578CED90B` (`from_id`),
  KEY `IDX_EEB02E7530354A65` (`to_id`),
  CONSTRAINT `FK_EEB02E7530354A65` FOREIGN KEY (`to_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_EEB02E7578CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `user_subscription_id` int(11) DEFAULT NULL,
  `amount` decimal(9,3) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_35259A0788C4EB53` (`user_subscription_id`),
  KEY `IDX_35259A07586DFF2` (`user_info_id`),
  CONSTRAINT `FK_35259A07586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_35259A0788C4EB53` FOREIGN KEY (`user_subscription_id`) REFERENCES `user_subscription` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_pref` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `email_project_digest` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `email_project_bids` tinyint(1) NOT NULL,
  `email_project_invites` tinyint(1) NOT NULL,
  `email_new_projects` tinyint(1) NOT NULL,
  `email_vocalist_suggestions` tinyint(1) NOT NULL,
  `activity_filter` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `email_messages` tinyint(1) NOT NULL DEFAULT '1',
  `email_connections` tinyint(1) NOT NULL DEFAULT '1',
  `email_tag_voting` tinyint(1) NOT NULL,
  `email_new_collabs` tinyint(1) NOT NULL,
  `connect_restrict_subscribed` tinyint(1) NOT NULL DEFAULT '0',
  `connect_restrict_certified` tinyint(1) NOT NULL DEFAULT '0',
  `connect_accept` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DBD4D4F8586DFF2` (`user_info_id`),
  CONSTRAINT `FK_DBD4D4F8586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `user_pref` (`id`, `user_info_id`, `email_project_digest`, `email_project_bids`, `email_project_invites`, `email_new_projects`, `email_vocalist_suggestions`, `activity_filter`, `email_messages`, `email_connections`, `email_tag_voting`, `email_new_collabs`, `connect_restrict_subscribed`, `connect_restrict_certified`, `connect_accept`, `updated_at`) VALUES
(1,	16,	'instantly',	1,	1,	1,	1,	'all',	1,	1,	1,	0,	0,	0,	1,	NULL),
(2,	30,	'instantly',	1,	1,	1,	1,	'all',	1,	1,	1,	0,	0,	0,	1,	NULL),
(3,	31,	'instantly',	1,	1,	1,	1,	'all',	1,	1,	1,	0,	0,	0,	1,	NULL),
(4,	32,	'instantly',	1,	1,	1,	1,	'all',	1,	1,	1,	0,	0,	0,	1,	NULL),
(5,	33,	'instantly',	1,	1,	1,	1,	'all',	1,	1,	1,	0,	0,	0,	1,	NULL),
(6,	36,	'instantly',	1,	1,	1,	1,	'all',	1,	1,	1,	0,	0,	0,	1,	NULL),
(7,	37,	'instantly',	1,	1,	1,	1,	'all',	1,	1,	1,	0,	0,	0,	1,	NULL);

CREATE TABLE `user_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `reviewed_by_id` int(11) DEFAULT NULL,
  `rating` double NOT NULL,
  `quality_of_work` int(11) NOT NULL,
  `communication` int(11) NOT NULL,
  `professionalism` int(11) NOT NULL,
  `work_with_again` int(11) NOT NULL,
  `on_time` tinyint(1) DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `hide` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1C119AFB586DFF2` (`user_info_id`),
  KEY `IDX_1C119AFB166D1F9C` (`project_id`),
  KEY `IDX_1C119AFBFC6B21F1` (`reviewed_by_id`),
  CONSTRAINT `FK_1C119AFB166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_1C119AFB586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_1C119AFBFC6B21F1` FOREIGN KEY (`reviewed_by_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_sc_track` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `sc_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `permalink_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `stream_url` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `genre` int(11) DEFAULT NULL,
  `bpm` int(11) DEFAULT NULL,
  `user_favorite` tinyint(1) NOT NULL,
  `raw_api_result` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F0374EFB586DFF2` (`user_info_id`),
  KEY `sc_idx` (`sc_id`),
  CONSTRAINT `FK_F0374EFB586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  `public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C779A692586DFF2` (`user_info_id`),
  CONSTRAINT `FK_C779A692586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_spotify_playlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `spotifyId` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `userId` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7BB7B74586DFF2` (`user_info_id`),
  CONSTRAINT `FK_7BB7B74586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_stat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_viewied` int(11) NOT NULL,
  `in_search_results` int(11) NOT NULL,
  `heard` int(11) NOT NULL,
  `active_gigs` int(11) NOT NULL,
  `completed_gigs` int(11) NOT NULL,
  `rated` int(11) NOT NULL,
  `average_rating` double NOT NULL,
  `tagged` int(11) NOT NULL,
  `followers` int(11) NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `subscription_plan_id` int(11) DEFAULT NULL,
  `stripe_subscr_id` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `paypal_subscr_id` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_commenced` datetime DEFAULT NULL,
  `date_ended` datetime DEFAULT NULL,
  `last_payment_date` datetime DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `cancel_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_230A18D1586DFF2` (`user_info_id`),
  KEY `IDX_230A18D19B8CE200` (`subscription_plan_id`),
  CONSTRAINT `FK_230A18D1586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_230A18D19B8CE200` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plan` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `tagged_by_id` int(11) DEFAULT NULL,
  `tag` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E89FD608586DFF2` (`user_info_id`),
  KEY `IDX_E89FD608B0156D6A` (`tagged_by_id`),
  KEY `tag_idx` (`tag`),
  CONSTRAINT `FK_E89FD608586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_E89FD608B0156D6A` FOREIGN KEY (`tagged_by_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `success` tinyint(1) NOT NULL,
  `amount` decimal(9,3) NOT NULL,
  `response` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DB2CCC44586DFF2` (`user_info_id`),
  CONSTRAINT `FK_DB2CCC44586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `provider` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sortNumber` int(11) NOT NULL DEFAULT '100',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4CF87F0F586DFF2` (`user_info_id`),
  CONSTRAINT `FK_4CF87F0F586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_vocal_characteristic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `vocal_characteristic_id` int(11) DEFAULT NULL,
  `agree` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_ECEEA2E5586DFF2` (`user_info_id`),
  KEY `IDX_ECEEA2E5AB3A6FD2` (`vocal_characteristic_id`),
  CONSTRAINT `FK_ECEEA2E5586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_ECEEA2E5AB3A6FD2` FOREIGN KEY (`vocal_characteristic_id`) REFERENCES `vocal_characteristic` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_vocal_characteristic_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_info_id` int(11) DEFAULT NULL,
  `user_vocal_characteristic_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_51D42D1438C00514` (`from_user_info_id`),
  KEY `IDX_51D42D1412EBC683` (`user_vocal_characteristic_id`),
  CONSTRAINT `FK_51D42D1412EBC683` FOREIGN KEY (`user_vocal_characteristic_id`) REFERENCES `user_vocal_characteristic` (`id`),
  CONSTRAINT `FK_51D42D1438C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_vocal_style` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `vocal_style_id` int(11) DEFAULT NULL,
  `agree` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C91971B4586DFF2` (`user_info_id`),
  KEY `IDX_C91971B49DDCAC1B` (`vocal_style_id`),
  CONSTRAINT `FK_C91971B4586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_C91971B49DDCAC1B` FOREIGN KEY (`vocal_style_id`) REFERENCES `vocal_style` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_vocal_style_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_info_id` int(11) DEFAULT NULL,
  `user_vocal_style_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_383E68AA38C00514` (`from_user_info_id`),
  KEY `IDX_383E68AAF6597342` (`user_vocal_style_id`),
  CONSTRAINT `FK_383E68AA38C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_383E68AAF6597342` FOREIGN KEY (`user_vocal_style_id`) REFERENCES `user_vocal_style` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_voice_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `voice_tag_id` int(11) DEFAULT NULL,
  `agree` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C2A16C98586DFF2` (`user_info_id`),
  KEY `IDX_C2A16C987D14E76D` (`voice_tag_id`),
  CONSTRAINT `FK_C2A16C98586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_C2A16C987D14E76D` FOREIGN KEY (`voice_tag_id`) REFERENCES `voice_tag` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_voice_tag_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_info_id` int(11) DEFAULT NULL,
  `user_voice_tag_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B6C5538E38C00514` (`from_user_info_id`),
  KEY `IDX_B6C5538EFDD89B6F` (`user_voice_tag_id`),
  CONSTRAINT `FK_B6C5538E38C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_B6C5538EFDD89B6F` FOREIGN KEY (`user_voice_tag_id`) REFERENCES `user_voice_tag` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_wallet_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `amount` int(11) NOT NULL,
  `currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AB9E1F89586DFF2` (`user_info_id`),
  CONSTRAINT `FK_AB9E1F89586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `user_wallet_transaction` (`id`, `user_info_id`, `description`, `amount`, `currency`, `data`, `created_at`) VALUES
(1,	16,	'Escrow payment to contest {project}',	-30000,	'USD',	'{\"projectTitle\":\"Test contest manddarin\",\"projectUuid\":\"5afa677b674c1\",\"projectType\":\"contest\"}',	'2018-05-15 11:52:16');

CREATE TABLE `user_withdraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `paypal_email` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `status_reason` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `user_ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5553BB9E586DFF2` (`user_info_id`),
  CONSTRAINT `FK_5553BB9E586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `vocalizr_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `actioned_user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `activity_type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `activity_read` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_546B11C4586DFF2` (`user_info_id`),
  KEY `IDX_546B11C4ABF1F0F6` (`actioned_user_info_id`),
  KEY `IDX_546B11C4166D1F9C` (`project_id`),
  CONSTRAINT `FK_546B11C4166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_546B11C4586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_546B11C4ABF1F0F6` FOREIGN KEY (`actioned_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `vocalizr_activity` (`id`, `user_info_id`, `actioned_user_info_id`, `project_id`, `activity_type`, `data`, `created_at`, `activity_read`) VALUES
(1,	NULL,	30,	NULL,	'new_member',	'{\"user_info\":{\"id\":30,\"username\":\"deepson1\",\"gender\":\"m\",\"is_vocalist\":true,\"is_producer\":true}}',	'2018-05-10 13:48:50',	0),
(2,	NULL,	31,	NULL,	'new_member',	'{\"user_info\":{\"id\":31,\"username\":\"deepson2\",\"gender\":\"m\",\"is_vocalist\":true,\"is_producer\":true}}',	'2018-05-10 13:51:03',	0),
(3,	NULL,	32,	NULL,	'new_member',	'{\"user_info\":{\"id\":32,\"username\":\"deepson3\",\"gender\":\"m\",\"is_vocalist\":true,\"is_producer\":true}}',	'2018-05-10 13:54:20',	0),
(4,	NULL,	16,	2,	'new_project',	'[]',	'2018-05-15 11:52:16',	0),
(5,	NULL,	33,	NULL,	'new_member',	'{\"user_info\":{\"id\":33,\"username\":\"test123\",\"gender\":\"m\",\"is_vocalist\":true,\"is_producer\":true}}',	'2018-05-16 14:44:36',	0),
(6,	NULL,	36,	NULL,	'new_member',	'{\"user_info\":{\"id\":36,\"username\":\"test12345\",\"gender\":\"m\",\"is_vocalist\":true,\"is_producer\":true}}',	'2018-05-21 11:54:24',	0),
(7,	NULL,	37,	NULL,	'new_member',	'{\"user_info\":{\"id\":37,\"username\":\"test123456\",\"gender\":\"m\",\"is_vocalist\":true,\"is_producer\":true}}',	'2018-05-21 12:45:35',	0);

CREATE TABLE `vocal_characteristic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `vocal_characteristic` (`id`, `title`) VALUES
(1,	'Raspy'),
(2,	'Rough'),
(3,	'Smoothe'),
(4,	'Silky'),
(5,	'Strong'),
(6,	'Crisp'),
(7,	'Deep'),
(8,	'High'),
(9,	'Low');

CREATE TABLE `vocal_style` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `vocal_style` (`id`, `title`) VALUES
(1,	'Rock'),
(2,	'Diva'),
(3,	'Divo'),
(4,	'Soulful'),
(5,	'Heavy Metal'),
(6,	'Death Metal'),
(7,	'Rap'),
(8,	'Choir'),
(9,	'Opera'),
(10,	'Country'),
(11,	'Reggae'),
(12,	'Spoken Word'),
(13,	'Classical'),
(14,	'Pop Diva'),
(15,	'Pop Divo'),
(16,	'Musical Theatre'),
(17,	'A Capella');

CREATE TABLE `voice_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- 2018-05-24 08:44:55
