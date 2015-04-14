<?php
/*
 *	paperdoll.php
 *
 *	Ultima Online Paperdoll Image Generator
 *	Vorspire - http://core.vita-nex.com
 *
 *	License: MIT
 *	Created: 05/2011
 *	Updated: 01/2015
 *
 *	HTML Usage: <img src="path/to/paperdoll.php?id=123" />
 *
 *	Previewing: Visit the link directly in your browser: http://www.domain.com/path/to/paperdoll.php?id=123
 */

if(isset($_REQUEST['id']))
	$loadID = $_REQUEST['id'];

$paperdoll = new Paperdoll($loadID);

class Paperdoll
{
	var $Debug;
	var $Logs;
	
	var $Load;

	var $Char;

	var $NameTitle;
	var $Index;
	var $Female;
	var $Hue;
	var $Gump;
	
	var $Hues_Mul = false;
	var $TileData_Mul = false;
	var $Gump_Mul = false;
	var $Gump_Idx = false;
		
	var $Image_Temp;
	
	var $Name;
	var $Title;
	
	var $Width;
	var $Height;

	var $Database;

	function Paperdoll($load)
	{
		$this->Load = $load;
		$this->Initialize();
	}
	
	function Initialize()
	{
		include_once "config.php";
		
		$this->Debug = DEBUG;
		
		$this->InitDB();
		
		$this->InitChar();		
		
		if(!isset($this->Char))
		{
			if($this->Debug)
				$this->Logs[] = "Character not found!";
		}
		else
		{
			$this->InitMulFiles();
		
			$this->RemoveGuildStrings();
			$this->SetBodyIndex();
			$this->SetBodyHue();		
			$this->SetDimensions(); //Optional ($Height,$Width)
			$this->InitItems();	
			$this->BuildGump();
			$this->FormatText();				
			$this->AddText();
		}
		
		if($this->Debug)
		{
			foreach($this->Logs as $line)
				echo $line . "<br/>";
		}
		else if(isset($this->Char))
			$this->CreateGump();
		
		$this->KillFiles();		
	}
	
	function InitDB()
	{
		if($this->Debug)
			$this->Logs[] = "Initializing Database...";
	
		include_once "database.php";
		
		$this->Database = $database;
		
		if(isset($this->Database))
		{
			if($this->Debug)
				$this->Logs[] = "Done!";
		}
		else
		{
			if($this->Debug)
				$this->Logs[] = "Failed!";
		}
	}
	
	function InitChar()
	{	
		if($this->Debug)
			$this->Logs[] = "Initializing Character...";
	
		$q = "SELECT * FROM " . TBL_CHARS . " WHERE id='" . $this->Load . "'";
		
		if($this->Debug)
			$this->Logs[] = "Query Database: ".$q;
				
		$this->Char = $this->Database->fquery($q);
		
		if($this->Char)
		{
			if($this->Debug)
				$this->Logs[] = "Done!";
		}
		else
		{
			if($this->Debug)
				$this->Logs[] = "Failed!";
		}
	}
			
	function RemoveGuildStrings()
	{
		if($this->Debug)
			$this->Logs[] = "Removing Guild Strings...";
	
		$this->NameTitle = str_replace(array("(Order)", "(Chaos)"), "", $this->Char['noto_title']);
		
		if($this->Debug)
			$this->Logs[] = "Done!";
	}
	
	function SetBodyIndex()
	{
		if($this->Debug)
			$this->Logs[] = "Setting Body Index...";
	
		if($this->Char['female'])
		{
			$this->Index = "13";
			$this->Female = "1";
		}
		else
		{
			$this->Index = "12";
			$this->Female = "0";
		}
		
		if($this->Debug)
			$this->Logs[] = "Index: " . $this->Index . " * Female: " . $this->Female;
		
		if($this->Debug)
			$this->Logs[] = "Done!";
	}
	
	function SetBodyHue()
	{
		if($this->Debug)
			$this->Logs[] = "Setting BodyHue...";
	
		$this->Hue = $this->Char['bodyhue'];
		$this->Gump = "1";
		
		if($this->Debug)
			$this->Logs[] = "BodyHue: " . $this->Hue . " * Gump: " . $this->Gump;
		
		if($this->Debug)
			$this->Logs[] = "Done!";
	}
	
	function InitItems()
	{	
		if($this->Debug)
			$this->Logs[] = "Initializing Items...";
	
		$q = "SELECT * FROM " . TBL_CHARS_LAYERS . " WHERE char_id='" . $this->Char['char_id'] . "' ORDER BY layer_id";
		
		if($this->Debug)
			$this->Logs[] = "Query Database: " . $q;
				
		$result = $this->Database->query($q);
		
		$items = array(array());
		$doSort = false;
		$num = 0;

		for ($i = 0; $item = mysqli_fetch_array($result); $i++)
		{
			if($this->Debug)
				$this->Logs[] = "Found Item - Parsing...";
			
			if($this->Debug)
				$this->Logs[] = "Item ID: " . $item['item_id'] . " * Layer: " . $item['layer_id'] . " * Hue: " . $item['item_hue'];
		
			$items['ids'][$num] = $item['item_id'];
			$items['hues'][$num] = $item['item_hue'];
			
			if ($item['layer_id'] == 13)
			{
				$items['layers'][$num++] = 3.5; // Fix for tunic
				$doSort = true;
			}
			else
			{
				$items['layers'][$num++] = $item['layer_id'];
			}
		}

		if ($doSort)
		{
			if($this->Debug)
				$this->Logs[] = "Do Sort Items...";
		
			array_multisort($items['layers'], SORT_ASC, SORT_NUMERIC, $items['ids'], SORT_ASC, SORT_NUMERIC, $items['hues'], SORT_ASC, SORT_NUMERIC);
		}
				
		if($this->Debug)
			$this->Logs[] = "Insert Items into Variables...";
				
		for ($i = 0; $i < $num; $i++)
		{
			// Insert items into variables
			$this->Index .= "," . $items['ids'][$i];
			$this->Hue .= "," . $items['hues'][$i];
					  
			if ($this->Char['female'] == 1)
			{				
				$this->Female .= ",1";
			}
			else
			{
				$this->Female .= ",0";				
			}
			
			$this->Gump .= ",0";
		}
		
		if($this->Debug)
		{
			$this->Logs[] = "Index-> " . $this->Index . "";
			$this->Logs[] = "Female-> " . $this->Female . "";
			$this->Logs[] = "Gump-> " . $this->Gump . "";
			$this->Logs[] = "Hue-> " . $this->Hue . "";
			$this->Logs[] = "Done!";
		}
	}
	
	function SetDimensions($w = 266, $h = 285)
	{
		if($this->Debug)
			$this->Logs[] = "Set Dimesions: W:" . $w . " H:" . $h;
	
		$this->Width = $w;
		$this->Height = $h;
	}
		
	function InitMulFiles()
	{
		if($this->Debug)
			$this->Logs[] = "Initializing Mul files...";
	
		$this->Hues_Mul = fopen("hues.mul", "rb");
				
		if (!$this->Hues_Mul)
		{
			if($this->Debug)
				$this->Logs[] = "Failed! (Hues.mul)";
			
			die("Unable to open hues.mul - ERROR\nDATAEND!");
			exit;
		}

		$this->TileData_Mul = fopen("tiledata.mul", "rb");		
		
		if (!$this->TileData_Mul)
		{
			if($this->Debug)
				$this->Logs[] = "Failed! (TileData.mul)";
		
			fclose($this->Hues_Mul);
			
			die("Unable to open tiledata.mul - ERROR\nDATAEND!");
			exit;
		}

		$this->Gump_Mul = fopen("gumpart.mul", "rb");
		
		if (!$this->Gump_Mul)
		{
			if($this->Debug)
				$this->Logs[] = "Failed! (GumpArt.mul)";
		
			fclose($this->Hues_Mul);
			fclose($this->TileData_Mul);
			
			die("Unable to open gumpart.mul - ERROR\nDATAEND!");
			exit;
		}

		$this->Gump_Idx = fopen("gumpidx.mul", "rb");
		
		if (!$this->Gump_Idx)
		{
			if($this->Debug)
				$this->Logs[] = "Failed! (GumpIdx.mul)";
		
			fclose($this->Hues_Mul);
			fclose($this->TileData_Mul);
			fclose($this->Gump_Mul);
			
			die("Unable to open gumpidx.mul - ERROR\nDATAEND!");
			exit;
		}
		
		if($this->Debug)
			$this->Logs[] = "Done!";
	}
	
	function BuildGump()
	{	
		if($this->Debug)
		{
			$this->Logs[] = "Building The Gump...";
		
			$this->Logs[] = "Verifying Mul Files:";
			$this->Logs[] = "TileData: " . $this->TileData_Mul;
			$this->Logs[] = "Hues: " . $this->Hues_Mul;
			$this->Logs[] = "Gump: " . $this->Gump_Mul;
			$this->Logs[] = "Gump Index: " . $this->Gump_Idx;
			
			$this->Logs[] = "Done!";
		}
	
		$this->InitializeGump($this->Width, $this->Height);
		
		if($this->Debug)
			$this->Logs[] = "Parsing Values...";
		
		if (strpos($this->Index, ","))
		{
			$rawIndex = explode(",", $this->Index);
			$rawFemale = explode(",", $this->Female);
			$rawHue = explode(",", $this->Hue);
			$rawGump = explode(",", $this->Gump);
			
			if($this->Debug)
				$this->Logs[] = "Done!";
		}
		else
		{
			$rawIndex = array($this->Index);
			$rawFemale = array($this->Female);
			$rawHue = array($this->Hue);
			$rawGump = array($this->Gump);
			
			if($this->Debug)
				$this->Logs[] = "Done!";
		}
		
		if($this->Debug)
			$this->Logs[] = "Parsing Each Item...";
		
		for ($i = 0; $i < sizeof($rawIndex); $i++)
		{
			$index = intval($rawIndex[$i]);
			$female = intval($rawFemale[$i]);
			$hue = intval($rawHue[$i]);
			$isGump = intval($rawGump[$i]);
			
			if ($female >= 1)
				$female = 1;
			else
				$female = 0;

			if ($hue < 1 || $hue > 65535)
				$hue = 0;

			if($isGump > 0 || $index == 12 || $index == 13)
				$isGump = 1;
			else
				$isGump = 0;
				
			if($this->Debug)
				$this->Logs[] = "Index: " . $index . " * Female: " . $female . " * Hue: " . $hue . " * Gump: " . $isGump;

			if ($index > 0x3FFF || $index <= 0 || $hue > 65535 || $hue < 0)
				continue;

			if ($isGump == 1) // Male/Female Gumps or Gump Param
				$gumpID = $index;
			else
			{
				$group = $index / 32;
				$groupIdx = $index % 32;
				
				fseek($this->TileData_Mul, 512 * 836 + 1188 * $group + 4 + $groupIdx * 37, SEEK_SET);
				
				if (feof($this->TileData_Mul))
					continue;

				// Read the flags
				$flags = $this->GetValueFromFile($this->TileData_Mul, POST_HS ? 8 : 4);
				
				if ($flags == -1)
				{
					if($this->Debug)
						$this->Logs[] = "Flags Say VOID, move to next Item...";
						
					continue;
				}
				
				if ($flags & (POST_AOS || POST_HS ? 0x404002 : 0x400000))
				{
					fseek($this->TileData_Mul, 6, SEEK_CUR);
					
					$gumpID = $this->GetValueFromFile($this->TileData_Mul, 2);
					$gumpID = $gumpID & 0xFFFF;
					
					if ($gumpID > 65535 || $gumpID <= 0)
					{
						if($this->Debug)
							$this->Logs[] = "Gump ID is Invalid, move to next Item...";
						
						continue;
					}

					if ($gumpID < 10000)
					{
						if ($female)
							$gumpID += 60000;
						else
							$gumpID += 50000;
					}
				}
				else
				{
					if($this->Debug)
						$this->Logs[] = "Flags Say NOT WEARABLE, move to next Item...";
						
					continue;
				}
			}

			if($this->Debug)
				$this->Logs[] = "Load The Raw Gump...";

			$this->LoadRawGump($gumpID, $hue);
		}
	}
	
	function LoadRawGump($gumpID, $hue)
	{
		$sendData = '';
		$color32 = array();

		fseek($this->Gump_Idx, $gumpID * 12, SEEK_SET);

		if (feof($this->Gump_Idx))
			return; // Invalid gumpid, reached end of gumpindex.

		$lookUp = $this->GetValueFromFile($this->Gump_Idx, 4);

		if ($lookUp == -1)
		{
			if ($index >= 60000)
				$index -= 10000;
				
			fseek($this->Gump_Idx, $gumpID * 12, SEEK_SET);

			if (feof($this->Gump_Idx)) // Invalid gumpid, reached end of gumpindex.
				return;

			$lookUp = $this->GetValueFromFile($this->Gump_Idx, 4);

			if ($lookUp == -1)
				return; // Gumpindex returned invalid lookup.
		}
		
		$gumpSize = $this->GetValueFromFile($this->Gump_Idx, 4);
		$gumpExtra = $this->GetValueFromFile($this->Gump_Idx, 4);
		
		fseek($this->Gump_Idx, $gumpID * 12, SEEK_SET);
		
		$gumpWidth = ($gumpExtra >> 16) & 0xFFFF;
		$gumpHeight = $gumpExtra & 0xFFFF;
		
		$sendData .= sprintf("Lookup: " . $lookUp . "\n");
		$sendData .= sprintf("Size: " . $gumpSize . "\n");
		$sendData .= sprintf("Height: " . $gumpHeight . "\n");
		$sendData .= sprintf("Width: " . $gumpWidth . "\n");

		if ($gumpWidth <= 0 || $gumpHeight <= 0)
			return; // Gump width or height was less than 0.

		fseek($this->Gump_Mul, $lookUp, SEEK_SET);

		$heightTable = $this->GetValueFromFile($this->Gump_Mul, ($gumpHeight * 4));

		if (feof($this->Gump_Mul))
			return; // Invalid gumpid, reached end of gumpfile.

		$sendData .= sprintf("DATASTART:\n");

		if ($hue <= 0)
		{
			if($this->Debug)
				$this->Logs[] = "No Hue Recolor Needed...";
		
			for ($y = 1; $y < $gumpHeight; $y++)
			{
				fseek($this->Gump_Mul, $heightTable[$y] * 4 + $lookUp, SEEK_SET);

				// Start of row
				$x = 0;

				while ($x < $gumpWidth)
				{
					$rle = $this->GetValueFromFile($this->Gump_Mul, 4);  // Read the RLE data
					$length = ($rle >> 16) & 0xFFFF;  // First two bytes - how many pixels does this color cover
					$color = $rle & 0xFFFF;  // Second two bytes - what color do we apply

					// Begin RGB value decoding
					$r = ($color >> 10) * 8;
					$g = (($color >> 5) & 0x1F) * 8;
					$b = ($color & 0x1F) * 8;

					if ($r > 0 || $g > 0 || $b > 0)
						$sendData.= sprintf($x . ":" . $y . ":" . $r . ":" . $g . ":" . $b . ":" . $length . "***");

					$x += $length;
				}
			}
		}
		else
		{
			if($this->Debug)
				$this->Logs[] = "Use the Hues File to Get Hue...";
		
			$hue = $hue - 1;
			$originalHue = $hue;

			if ($hue > 0x8000)
				$hue = $hue - 0x8000;

			if ($hue > 3001) // Bad hue will cause a crash
				$hue = 1;

			$colors = ($hue / 8) * 4;
			$colors = 4 + $hue * 88 + $colors;
			
			if($this->Debug)
				$this->Logs[] = "COLOR: " . $colors;

			fseek($this->Hues_Mul, $colors, SEEK_SET);

			for ($i = 0; $i < 32; $i++)
			{
				$color32[$i] = $this->GetValueFromFile($this->Hues_Mul, 2);
				$color32[$i] |= 0x8000;
			}

			for ($y = 1; $y < $gumpHeight; $y++)
			{
				fseek($this->Gump_Mul, $heightTable[$y] * 4 + $lookUp, SEEK_SET);

				// Start of row
				$x = 0;

				while ($x < $gumpWidth)
				{
					$rle = $this->GetValueFromFile($this->Gump_Mul, 4);  // Read the RLE data
					$length = ($rle >> 16) & 0xFFFF;  // First two bytes - how many pixels does this color cover
					$color = $rle & 0xFFFF;  // Second two bytes - what color do we apply

					// Begin RGB value decoding
					$r = $color >> 10;
					$g = ($color >> 5) & 0x1F;
					$b = $color & 0x1F;

					// Check if we're applying a special hue (skin hues), if so, apply only to grays
					if ($originalHue > 0x8000 && $r == $g && $r == $b)
					{
						$newR = ($color32[$r] >> 10) *8;
						$newG = (($color32[$r] >> 5) & 0x1F) * 8;
						$newB = ($color32[$r] & 0x1F) * 8;
					}
					else if ($originalHue > 0x8000)
					{
						$newR = $r * 8;
						$newG = $g * 8;
						$newB = $b * 8;
					}
					else
					{
						$newR = ($color32[$r] >> 10) * 8;
						$newG = (($color32[$r] >> 5) & 0x1F) * 8;
						$newB = ($color32[$r] & 0x1F) * 8;
					}
					
					if(($r * 8 > 0) || $g * 8 > 0 || $b * 8 > 0)
						$sendData.= sprintf($x . ":" . $y . ":" . $newR . ":" . $newG . ":" . $newB . ":" . $length . "***");

					$x += $length;
				}
			}
		}

		$sendData .= sprintf("DATAEND!");

		$this->AddGump($sendData);
	}
	
	function InitializeGump($width, $height)
	{
		$this->Image_Temp = imagecreatetruecolor($width, $height) or die("couldnt create image");
	
		$transColor = imagecolorallocate($this->Image_Temp, 255, 64, 255);	
		imageColorTransparent($this->Image_Temp, $transColor);	
		imagealphablending($this->Image_Temp, true);
	}

	function FormatText()
	{
		// Separate name and skill title
		$this->NameTitle = $this->striphtmlchars($this->NameTitle);
		
		if (($i = strpos($this->NameTitle, ",")) !== false)
		{
			$this->Name = substr($this->NameTitle, 0, $i);
			$this->Title = substr($this->NameTitle, $i + 2);
		}
		else
		{
			$toRemove = array("(Order)","(Chaos)");
			$textResult = str_replace($toRemove, "", $this->NameTitle);
			$this->Name = $this->Char['name'];
			$this->Title = "";
		}
	}

	function KillFiles()
	{
		fclose($this->Hues_Mul);
		fclose($this->TileData_Mul);
		fclose($this->Gump_Mul);
		fclose($this->Gump_Idx);
		exit;
	}

	function GetValueFromFile($file, $length)
	{
		if (($value = fread($file, $length)) == false)
		{
			if($this->Debug)
				$this->Logs[] = "Get Value From File returned VOID...";
			
			return -1;
		}
		
		$value = $this->UnpackSigned($value, $length * 8);
		
		if($this->Debug)
			$this->Logs[] = "Get Value From " . 
			($file == $this->TileData_Mul ? "TileData:" : 
			($file == $this->Hues_Mul ? "Hues:" : 
			($file == $this->Gump_Mul ? "Gump:" : 
			($file == $this->Gump_Idx ? "GumpIndex:" : 
			"File")))) . " returned ". $value[1] . "...";
		
		return $value[1];
	}
	
	function UnpackSigned($data, $bits = 8)
	{
		$t = $bits / 8;
		$r =  $bits % 8;
		
		if($r !== 0)
			$bits = 8 * $t;
		
		$code = 'a*error';
		
		switch($bits)
		{
			case 8: $code = 'c*char'; break;
			case 16: $code = 's*short'; break;
			case 32: $code = 'l*int'; break;
			case 64: $code = 'q*long'; break;
		}
		
		return unpack($code, $data);
	}

	function AddGump($sendData)
	{
		if (strpos($sendData, "ERROR"))
		{
			if($this->Debug)
				$this->Logs[] = "Add Gump: Returned ERROR in DataStream";
			
			return;
		}
		
		$data = explode("DATASTART:\n", $sendData);
		$data = $data[1];
		$newData = explode("***", $data);
	  
		while (list($key, $val) = @each($newData))
		{
			if($this->Debug)
				$this->Logs[] = "Add Gump: Key:" . $key . " + Val: " . $val;
		
			if ($val == "DATAEND!")
				break;			

			$val = explode(":", $val);
			
			$x = intval($val[0]);
			$y = intval($val[1]);
			$r = intval($val[2]);
			$g = intval($val[3]);
			$b = intval($val[4]);
			
			$length = intval($val[5]); // pixel color repeat length
			
			if ($r || $g || $b)
			{
				$colorAllocate = imagecolorallocate($this->Image_Temp, $r, $g, $b);

				for ($i = 0; $i < $length; $i++)
					imagesetpixel($this->Image_Temp, $x + $i, $y, $colorAllocate);
			}
		}
	}

	function AddText()
	{	
		$textColor = imagecolorallocate($this->Image_Temp, 255, 255, 0);
		$pos = 135 - (strlen($this->Name) * 3.5);

		if ($pos < 0)
			$pos = 0;

		imagestring($this->Image_Temp, 4, $pos, 240, $this->Name, $textColor);

		$pos = 140 - (strlen($this->Title) * 3.5);

		if ($pos < 0)
			$pos = 0;

		imagestring($this->Image_Temp, 3, $pos, 255, $this->Title, $textColor);
	}

	function CreateGump()
	{	
		header("Content-type: image/png");
		imagepng($this->Image_Temp);
		imagedestroy($this->Image_Temp);		
	}

	function striphtmlchars($text)
	{
		$text = str_replace("&amp;", "&", $text);
		$text = str_replace("&#39;", "'", $text);
		return $text;
	}
}

?>
