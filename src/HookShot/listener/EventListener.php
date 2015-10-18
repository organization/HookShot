<?php

namespace HookShot\listener;

use HookShot\database\PluginData;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Float;
use pocketmine\level\sound\LaunchSound;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\network\protocol\SetEntityLinkPacket;
use HookShot\entity\Bobber;

class EventListener implements Listener {
	/**
	 *
	 * @var Plugin
	 */
	private $plugin;
	/**
	 *
	 * @var Server
	 */
	private $server;
	private $db;
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		$this->db = PluginData::getInstance ();
		$this->server = Server::getInstance ();
		
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this->plugin );
	}
	public function registerCommand($name, $permission, $description, $usage) {
		$name = $this->db->get ( $name );
		$description = $this->db->get ( $description );
		$usage = $this->db->get ( $usage );
		$this->db->registerCommand ( $name, $permission, $description, $usage );
	}
	public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event) {
		$packet = $event->getPacket ();
		$player = $event->getPlayer ();
		
		if (! $packet instanceof UseItemPacket)
			return;
		
		if ($player->spawned === false or ! $player->isAlive () or $player->blocked)
			return;
		
		$blockVector = new Vector3 ( $packet->x, $packet->y, $packet->z );
		$player->craftingType = 0;
		$packet->eid = $player->getId ();
		
		$aimPos = (new Vector3 ( $packet->x / 32768, $packet->y / 32768, $packet->z / 32768 ))->normalize ();
		if ($player->isCreative ()) {
			$item = $player->getInventory ()->getItemInHand ();
		} elseif ($player->getInventory ()->getItemInHand ()->getId () !== $packet->item or (($damage = $player->getInventory ()->getItemInHand ()->getDamage ()) !== $packet->meta and $damage !== \null)) {
			$player->getInventory ()->sendHeldItem ( $player );
			return;
		} else {
			$item = $player->getInventory ()->getItemInHand ();
		}
		$player->getInventory ()->sendHeldItem ( $player );
		if ($item->getId () == 346) {
			if ($packet->face !== 0xff) {
				$event->setCancelled ();
				return;
			}
			$nbt = new Compound ( "", [ 
					"Pos" => new Enum ( "Pos", [ 
							new Double ( "", $player->x ),
							new Double ( "", $player->y + $player->getEyeHeight () ),
							new Double ( "", $player->z ) 
					] ),
					"Motion" => new Enum ( "Motion", [ 
							new Double ( "", $aimPos->x ),
							new Double ( "", $aimPos->y ),
							new Double ( "", $aimPos->z ) 
					] ),
					"Rotation" => new Enum ( "Rotation", [ 
							new Float ( "", $player->yaw ),
							new Float ( "", $player->pitch ) 
					] ) 
			] );
			
			$f = 1.5;
			$bobber = Entity::createEntity ( "Bobber", $player->chunk, $nbt, $player );
			$bobber->setMotion ( $bobber->getMotion ()->multiply ( $f ) );
			if ($player->isSurvival ()) {
				$item->setCount ( $item->getCount () - 1 );
				$player->getInventory ()->setItemInHand ( $item->getCount () > 0 ? $item : Item::get ( Item::AIR ) );
			}
			if ($bobber instanceof Projectile) {
				$player->getServer ()->getPluginManager ()->callEvent ( $projectileEv = new ProjectileLaunchEvent ( $bobber ) );
				if ($projectileEv->isCancelled ()) {
					$bobber->kill ();
				} else {
					$bobber->spawnToAll ();
					$player->getLevel ()->addSound ( new LaunchSound ( $player ), $player->getViewers () );
				}
			} else {
				$bobber->spawnToAll ();
			}
			$pk = new SetEntityLinkPacket ();
			$pk->from = $bobber->getId ();
			$pk->to = $player->getId ();
			$pk->type = 2;
			$this->getServer ()->broadcastPacket ( $player->getLevel ()->getPlayers (), $pk );
		}
	}
	public function getServer() {
		return $this->server;
	}
}

?>