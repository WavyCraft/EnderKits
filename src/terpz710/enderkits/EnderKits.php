<?php

declare(strict_types=1);

namespace terpz710\enderkits;

use pocketmine\plugin\PluginBase;

use terpz710\enderkits\command\KitCommand;

use terpz710\enderkits\api\KitManager;

use terpz710\enderkits\task\CooldownTask;

use CortexPE\Commando\PacketHooker;

use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class EnderKits extends PluginBase {

    protected static self $instance;

    protected DataConnector $db;

    protected function onLoad() : void{
        self::$instance = $this;
    }

    protected function onEnable() : void{
        $this->saveDefaultConfig();
        $this->saveResource("kits.yml");

        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        $this->getServer()->getCommandMap()->register("EnderKits", new KitCommand($this, "kit", "claim a kit"));

        $this->getScheduler()->scheduleRepeatingTask(new CooldownTask(), 20);

        $this->init();
    }

    protected function onDisable() : void{
        $this->db->close();
    }

    public static function getInstance() : self{
        return self::$instance;
    }

    protected function init() : void{
        $this->db = libasynql::create($this, $this->getConfig()->get("database"), [
            "sqlite" => "database/sqlite.sql",
            "mysql" => "database/mysql.sql"
        ]);

        $this->db->executeGeneric("table.cooldowns");
    }

    public function getDataBase() : DataConnector{
        return $this->db;
    }

    public function isUiEnabled() : bool{
        return (bool) $this->getConfig()->get("enable-ui");
    }
}
