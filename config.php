<?php
/*
 *	config.php
 *
 *	Ultima Online Paperdoll Image Generator
 *	Vorspire - http://core.vita-nex.com
 *
 *	License: MIT
 *	Created: 05/2011
 *	Updated: 01/2015
 */

/*Database*/
define("DB_SERVER", "127.0.0.1");
define("DB_PORT", "3306");
define("DB_NAME", "myrunuo");
define("DB_USER","ultima");
define("DB_PASS","******");

/*Character Tables*/
define("TBL_CHARS", "chars");
define("TBL_CHARS_LAYERS", "chars_layers");

/*Misc*/

/*
 *	When enabled, outputs plain text debug logs of the entire generation process.
 */
define("DEBUG", false);

/*
 *	Support client files from High Seas and above. 
 *	Typically client 7.0.9+
 *	TileData.mul uses 4 bytes (Int32) to store the Flags data in clients before 7.0.9
 *	TileData.mul uses 8 bytes (Int64) to store the Flags data in clients after and including 7.0.9
 */
define("POST_HS", true);

/*
 *	Support client files from Age of Shadows and above.
 *	TileData.mul uses 0x400000 for the Wearable flag in clients before AOS.
 *	TileData.mul uses 0x404002 for the Wearable flag in clients after and including AOS.
 *	If POST_HS is true, POST_AOS is ignored.
 */
define("POST_AOS", true);


?>