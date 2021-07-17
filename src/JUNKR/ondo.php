<?php

namespace JUNKR;

use ifteam\Farms\Farms;
use pocketmine\block\BlockFactory;
use pocketmine\block\NetherReactor;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;

class ondo extends PluginBase implements Listener{

    public $prefix = "§l§[온도] §r§7";

    public $db, $database;

    private static $instance = null;

    public static function getInstance() : self{
        return static::$instance;
    }

    public function onLoad(){
        self::$instance = $this;
    }

    public function onDisable(){
        $this->save();
    }

    public function save(){
        $this->database->setAll($this->db);
        $this->database->save();
    }

    public $ondo = 0;

    public function settick($int){
        Farms::getInstance()->get('farm-growing-time');
        Farms::getInstance()->setconfigdatatime($int);
    }

    public function onEnable(){
        $this->database = new Config($this->getDataFolder() . 'job.yml', Config::YAML, [
            "farm" => []
        ]);
        $this->db = $this->database->getAll();

       # BlockFactory::registerBlock(new \JUNKR\NetherReactor(), true);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->Rcommand("온도");

        $task = new class() extends Task{
            public function onRun(int $currentTick){
                ondo::getInstance()->get();
            }
        };

        $this->getScheduler()->scheduleRepeatingTask($task, 20 * 60 * 5);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($command->getName() === "온도"){
            $ondo = self::getondo();
            $sender->sendMessage("§l§a[온도] §r§7현재 온도는 §6{$ondo}도 §7입니다.");
        }
        return true;
    }

    public function get(){
        #$ch = curl_init(); // 리소스 초기화

        $url = "http://crsbe.kr/ondo";

        /* // 옵션 설정
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

         $ondo = curl_exec($ch); // 데이터 요청 후 수신

         curl_close($ch);  // 리소스 해제*/

        $ondo = Internet::getURL($url);

        $this->ondo = $ondo;

        if(self::getondo() === 20){
            $this->settick(600);
            return;
        }

        $tick = 20 - self::getondo();
        $tick = abs($tick) * 300 + 600;

        $this->settick($tick);
    }

    function Rcommand($name){
        $cmd = new PluginCommand($name, $this);
        $cmd->setDescription($name . ' 명령어 입니다');

        Server::getInstance()->getCommandMap()->register($this->getDescription()->getName(), $cmd);
    }

    public static function getondo(?Level $level = null) : int{
        if($level === null){
            return (int) ondo::getInstance()->ondo;
        }
        if(isset(self::getInstance()->db[$level->getFolderName()])){
            return intval(self::getInstance()->db[$level->getFolderName()]);
        }else{
            return (int) ondo::getInstance()->ondo;
        }
    }

}
