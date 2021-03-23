ALTER TABLE user_info 
ADD `country_id` int(11) DEFAULT NULL;

UPDATE user_info
SET country=UPPER(country);

UPDATE user_info
SET country='GB'
WHERE country='UK';

UPDATE user_info as u 
LEFT JOIN countries as c on u.country=c.code
SET u.country_id=c.id;

ALTER TABLE user_info 
DROP country;
