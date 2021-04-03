<?php

namespace xmoveton\dailybonus;

use xmoveton\dailybonus\cmd\DailybonusCommand;
use xmoveton\dailybonus\event\EventHandler;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class DailyBonus extends PluginBase {

    /**
     * @var DailyBonus
     */
    private static $instance = null;

    /**
     * @var SQLite3
     */
    private $sqlite = null;

    /**
     * @var Config
     */
    private $config = null;

    /**
     * @var Config
     */
    private $messages = null;

    /**
     * @var EconomyAPI
     */
    public $economy;

    public function onLoad () {
        self::$instance = & $this;
    }

    public function onEnable () {
        $f = $this->getDataFolder();

        $this->createConfig($f);

        // SQLite3 create 
        $sql = $this->getResource('table.sql');
        $this->sqlite = new \SQLite3($f . 'data.db');
        $this->sqlite->exec(stream_get_contents($sql));
        fclose($sql);

        // EconomyAPI and register event
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
        $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        ($this->economy) ?? $this->getLogger()->error("Установите плагин EconomyAPI!");

        // register command
        $this->getServer()->getCommandMap()->register('dailybonus', new DailybonusCommand($this, 'dailybonus'));
    }

    public function onDisable () {
        $this->sqlite->close();
    }

    private function createConfig ($f) {
        if (!(is_dir($f))) {
            @mkdir($f);
        }
        $this->saveResource('Messages.yml');
        $this->saveResource('Config.yml');

        $this->config = (new Config($f . 'Config.yml', Config::YAML))->getAll();
        $this->messages = (new Config($f . 'Messages.yml', Config::YAML))->getAll();
    }

    /**
     * @param Player|string $player
     * 
     * @return string
     */
    public function getPlayer ($player) {
        if ($player instanceof Player) {
            $player = $player->getName();
        }
        return strtolower($player);
    }

    /**
     * @param string $key
     * @param Player|string $player
     * @param array $value
     * 
     * @return string|boolean
     */
    public function getMessage ($key, array $value = ["%AMOUNT_DAYS%", "%DAILY_PRIZE%"]) {
        if (isset($this->messages[$key])) {
            return str_replace(["%AMOUNT_DAYS%", "%DAILY_PRIZE%"], [$value[0], $value[1]], $this->messages[$key]);
        }
        return false;
    }

    /**
     * @param Player|string $player
     * 
     * @return boolean
     */
    public function hasAccount ($player) {
        $player = $this->getPlayer($player);

        $user = $this->query("SELECT * FROM `db_bonus` WHERE `nickname` = '$player'")->fetchArray(SQLITE3_ASSOC);
        if ($user !== false) {
            return $user;
        }
        return false;
    }

    /**
     * @param Player|string $player
     * @param bool|int $default_days
     * 
     * @return boolean
     */
    public function createAccount ($player, $default_days = false) {
        $player = $this->getPlayer($player);

        if (!($this->hasAccount($player))) {
            $default_days = ($default_days === false) ? 0 : $default_days;
            $date = date('Y-m-d');
            $this->query("INSERT INTO `db_bonus` (`nickname`, `last_date`, `amount_days`) VALUES ('$player', '$date', '$default_days')");
            return true;
        }
        return false;
    }

    /**
     * @param Player|string $player
     * @param string $type
     * 
     * @return boolean
     */
    public function updateAccount ($player, $type = "CLEAR_ACCOUNT") {
        $player = $this->getPlayer($player);

        if ($this->hasAccount($player)) {
            $date = date('Y-m-d');
            switch ($type) {
                case "CLEAR_ACCOUNT":
                    $this->query("UPDATE `db_bonus` SET `amount_days` = 0, `last_date` = '$date'");
                    break;
                case "ADD_DAY":
                    $this->query("UPDATE `db_bonus` SET `amount_days` = amount_days + 1, `last_date` = '$date'");
                    break;
            }
            return true;
        }
        return false;
    }

    /**
     * @param Player|string $player
     * 
     * @return int|boolean
     */
    public function getPlayerDays ($player) {
        $player = $this->getPlayer($player);

        if ($user = $this->hasAccount($player)) {
            return $user['amount_days'];
        }
        return false;
    }

    /**
     * @param string $date
     * 
     * @return string|boolean
     */
    public function time ($date) {
        $today = date("Y-m-d", strtotime("now"));
        $ule = date("Y-m-d", strtotime($date));
        $yesterday = date("Y-m-d", strtotime("now -1 day"));

        return $ule == $today ? 'сегодня' : 
        ($ule == $yesterday ? 'вчера' : 'давно');
    }

    /**
     * @param string $sql
     * 
     * @return boolean
     */
    public function query ($sql) {
        return $this->sqlite->query($sql);
    }

    public function getAmount () {
        return $this->config['amount'];
    }

    /**
     * @return DailyBonus
     */
    public static function getInstance () : DailyBonus {
        return self::$instance;
    }
}