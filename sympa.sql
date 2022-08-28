

CREATE TABLE IF NOT EXISTS `sympa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT, 
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `mail` varchar(255) NOT NULL DEFAULT '',
  `mail_domain_id` int(10) UNSIGNED NOT NULL,
  `web` varchar(255) NOT NULL DEFAULT '',
  `web_domain_id` int(10) UNSIGNED NOT NULL,
  `websub` varchar(255) NOT NULL DEFAULT '',
  `listmasters` text NOT NULL DEFAULT '',
  `sympa_action` enum('OK','CREATE','DELETE', 'DELETING', 'REGENERATE') NOT NULL DEFAULT 'OK',
  `sympa_result` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `mail_domain_id` (`mail_domain_id`),
  KEY `sympa_action` (`sympa_action`)
) DEFAULT CHARSET=utf8mb4 COMMENT='Sympa mailing lists Robots';


CREATE TABLE IF NOT EXISTS alternc_status (name VARCHAR(48) NOT NULL DEFAULT '',value LONGTEXT NOT NULL,PRIMARY KEY (name),KEY name (name) ) DEFAULT CHARSET=latin1;

INSERT IGNORE INTO alternc_status SET name='alternc-sympa_version',value='1.0.sql';

-- the domaine type of Sympa for hosted virtual robots is only accessible to admins, simple users have no direct control over it, unless via the sympa alternc panel webpage
INSERT IGNORE INTO `domaines_type` VALUES ('sympa-robot','Web Sympa','NONE','%SUB% IN A @@PUBLIC_IP@@\n%SUB% IN AAAA 2001:67c:288:32::224','txt,defmx,defmx2,mx,mx2','ADMIN',0,0,1,0,0);
