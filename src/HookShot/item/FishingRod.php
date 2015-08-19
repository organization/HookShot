<?php

namespace HookShot\item;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\Player;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Float;

class FishingRod extends Item {
	public function __construct($meta = 0, $count = 1) {
		parent::__construct ( 346, $meta, $count, "FishingRod" );
	}
	public function canBeActivated() {
		return true;
	}
	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz) {
		$realPos = $block->getSide ( $face );
		
		$item = $player->getInventory ()->getItemInHand ();
		$count = $item->getCount ();
		if (-- $count <= 0) {
			$player->getInventory ()->setItemInHand ( Item::get ( Item::AIR ) );
			return;
		}
		
		$item->setCount ( $count );
		$player->getInventory ()->setItemInHand ( $item );
		return true;
	}
}
