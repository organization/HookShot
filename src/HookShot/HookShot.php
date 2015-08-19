<?php

namespace HookShot;

use HookShot\database\PluginData;
use HookShot\listener\EventListener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use HookShot\item\FishingRod;
use pocketmine\inventory\BigShapelessRecipe;

class HookShot extends PluginBase implements Listener {
	private $eventListener;
	/**
	 * Called when the plugin is enabled
	 *
	 * @see \pocketmine\plugin\PluginBase::onEnable()
	 */
	public function onEnable() {
		$this->database = new PluginData ( $this );
		$this->eventListener = new EventListener ( $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
		
		Item::$list [346] = FishingRod::class;
		Item::addCreativeItem ( new Item ( 346 ) );
		$this->getServer ()->addRecipe ( (new BigShapelessRecipe ( Item::get ( 346, 0, 1 ) ))->addIngredient ( Item::get ( Item::STICK, null, 3 ) )->addIngredient ( Item::get ( Item::STRING, null, 2 ) ) );
		Entity::registerEntity ( "\\HookShot\\entity\\Bobber", true );
	}
	/**
	 * Return Plug-in Event Listener
	 */
	public function getEventListener() {
		return $this->eventListener;
	}
}

?>