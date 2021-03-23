-- Adminer 4.6.2 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `engine_product`;
CREATE TABLE `engine_product`
(
    `id`          int(11)                              NOT NULL AUTO_INCREMENT,
    `code`        varchar(200) COLLATE utf8_unicode_ci NOT NULL,
    `amount`      int(11)                              NOT NULL,
    `title`       varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `description` longtext COLLATE utf8_unicode_ci     NOT NULL,
    `active`      tinyint(1)                           NOT NULL,
    `pro_only`    tinyint(1)                           NOT NULL,
    `sort_order`  int(11)                              NOT NULL,
    `created_at`  datetime                             NOT NULL,
    `updated_at`  datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `genre`;
CREATE TABLE `genre`
(
    `id`    int(11)                             NOT NULL AUTO_INCREMENT,
    `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

INSERT INTO `genre` (`id`, `title`)
VALUES (1, 'Electronica'),
       (2, 'Progressive House'),
       (3, 'Trance'),
       (4, 'Tech'),
       (5, 'Techno'),
       (6, 'Electro'),
       (7, 'Drum N Bass'),
       (8, 'House'),
       (9, 'Dubstep'),
       (10, 'Chill Out'),
       (11, 'Hardcore'),
       (12, 'Indie Dance'),
       (13, 'Nu Disco'),
       (14, 'Trap'),
       (15, 'Funk'),
       (16, 'RnB'),
       (17, 'Hip Hop'),
       (18, 'Rap'),
       (19, 'Rock'),
       (20, 'Heavey Metal'),
       (21, 'Prog Rock'),
       (22, 'Country / Western'),
       (23, 'Indie Rock'),
       (24, 'Punk'),
       (25, 'Pop'),
       (26, 'Blues'),
       (27, 'Soul'),
       (28, 'Opera'),
       (29, 'Reggae'),
       (30, 'Jazz'),
       (31, 'Hard Rock'),
       (32, 'Folk'),
       (33, 'Classical'),
       (34, 'Latin'),
       (35, 'Breaks');

DROP TABLE IF EXISTS `language`;
CREATE TABLE `language`
(
    `id`    int(11)                              NOT NULL AUTO_INCREMENT,
    `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

INSERT INTO `language` (`id`, `title`)
VALUES (1, 'English'),
       (2, 'Spanish'),
       (3, 'French'),
       (4, 'Dutch'),
       (5, 'Italian'),
       (6, 'Mandarin'),
       (7, 'Japanese'),
       (8, 'South Korean');

DROP TABLE IF EXISTS `subscription_plan`;
CREATE TABLE `subscription_plan`
(
    `id`                       int(11)                             NOT NULL AUTO_INCREMENT,
    `title`                    varchar(32) COLLATE utf8_unicode_ci NOT NULL,
    `description`              longtext COLLATE utf8_unicode_ci    NOT NULL,
    `price`                    int(11)                             NOT NULL,
    `user_audio_limit`         int(11)                                      DEFAULT NULL,
    `project_percent_added`    int(11)                             NOT NULL,
    `payment_percent_taken`    int(11)                             NOT NULL,
    `project_private_fee`      int(11)                             NOT NULL,
    `project_highlight_fee`    int(11)                             NOT NULL,
    `project_feature_fee`      int(11)                             NOT NULL,
    `project_announce_fee`     int(11)                             NOT NULL,
    `connect_month_limit`      int(11)                             NOT NULL DEFAULT ' 5 ',
    `message_month_limit`      int(11)                                      DEFAULT ' 5 ',
    `static_key`               varchar(32) COLLATE utf8_unicode_ci NOT NULL,
    `unique_key`               varchar(32) COLLATE utf8_unicode_ci NOT NULL,
    `updated_at`               datetime                                     DEFAULT NULL,
    `hidden`                   tinyint(1)                          NOT NULL,
    `created_at`               datetime                            NOT NULL,
    `project_restrict_fee`     int(11)                                      DEFAULT NULL,
    `project_favorites_fee`    int(11)                                      DEFAULT NULL,
    `project_messaging_fee`    int(11)                                      DEFAULT NULL,
    `project_lock_to_cert_fee` int(11)                                      DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

INSERT INTO `subscription_plan` (`id`, `title`, `description`, `price`, `user_audio_limit`, `project_percent_added`,
                                 `payment_percent_taken`, `project_private_fee`, `project_highlight_fee`,
                                 `project_feature_fee`, `project_announce_fee`, `connect_month_limit`,
                                 `message_month_limit`, `static_key`, `unique_key`, `updated_at`, `hidden`,
                                 `created_at`, `project_restrict_fee`, `project_favorites_fee`, `project_messaging_fee`,
                                 `project_lock_to_cert_fee`)
VALUES (1, 'Free Membership', 'Free Membership', 0, 2, 5, 20, 500000, 1000, 1000, 1000, 5, 5, 'FREE',
        '8e6d3d171e6b3bbbfb7428502e11628f', NULL, 0, '2018-05-08 17:57:34', 1000, 1000, 1000, 1000),
       (2, 'PRO Membership', 'PRO Membership', 0, 100, 0, 10, 500, 100, 100, 100, 5000, 5000, 'PRO',
        '8e6d3d171e6b3bbbfb7428502e11628f', NULL, 0, '2018-05-08 17:57:34', NULL, NULL, NULL, NULL);

DROP TABLE IF EXISTS `vocal_characteristic`;
CREATE TABLE `vocal_characteristic`
(
    `id`    int(11)                             NOT NULL AUTO_INCREMENT,
    `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

INSERT INTO `vocal_characteristic` (`id`, `title`)
VALUES (1, 'Raspy'),
       (2, 'Rough'),
       (3, 'Smoothe'),
       (4, 'Silky'),
       (5, 'Strong'),
       (6, 'Crisp'),
       (7, 'Deep'),
       (8, 'High'),
       (9, 'Low');

DROP TABLE IF EXISTS `vocal_style`;
CREATE TABLE `vocal_style`
(
    `id`    int(11)                             NOT NULL AUTO_INCREMENT,
    `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

INSERT INTO `vocal_style` (`id`, `title`)
VALUES (1, 'Rock'),
       (2, 'Diva'),
       (3, 'Divo'),
       (4, 'Soulful'),
       (5, 'Heavy Metal'),
       (6, 'Death Metal'),
       (7, 'Rap'),
       (8, 'Choir'),
       (9, 'Opera'),
       (10, 'Country'),
       (11, 'Reggae'),
       (12, 'Spoken Word'),
       (13, 'Classical'),
       (14, 'Pop Diva'),
       (15, 'Pop Divo'),
       (16, 'Musical Theatre'),
       (17, 'A Capella');

-- 2020-09-15 07:58:45
