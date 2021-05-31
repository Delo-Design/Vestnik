/*
 * @package     Vestnik Package
 * @subpackage  com_vestnik
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

CREATE TABLE IF NOT EXISTS `#__vestnik_hashtags`
(
    `id`          int(11)                                                NOT NULL AUTO_INCREMENT,
    `title`       varchar(255)                                           NOT NULL DEFAULT '',
    `alias`       varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
    `hashtag`     varchar(100)                                           NOT NULL DEFAULT '',
    `description` text                                                   NOT NULL,
    `state`       tinyint(3)                                             NOT NULL DEFAULT 0,
    `ordering`    int(11)                                                NOT NULL DEFAULT 0,
    `params`      json                                                   NULL,
    `plugins`     json                                                   NULL,
    PRIMARY KEY `id` (`id`),
    KEY `idx_alias` (`alias`(100)),
    KEY `idx_hashtag` (`hashtag`),
    KEY `idx_state` (`state`),
    KEY `idx_ordering` (`ordering`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    DEFAULT COLLATE = utf8mb4_unicode_ci
    AUTO_INCREMENT = 0;