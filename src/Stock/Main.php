<?php

namespace Stock;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\event\Listener;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;

use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\Entity\entity;

use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\Explosion;
use pocketmine\utils\TextFormat;
use pocketmine\utils\MainLogger;
use pocketmine\plugin\Plugin;

class Main extends PluginBase implements Listener{

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    if(!file_exists($this->getDataFolder())){
        mkdir($this->getDataFolder(), 0744, true);
    }
    if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
$this->EconomyAPI = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
$this->getLogger()->info("EconomyAPIを検出しました。");
}else{
$this->getLogger()->warning("EconomyAPIが見つかりませんでした");
$this->getServer()->getPluginManager()->disablePlugin($this);
}
    $this->stock = new Config($this->getDataFolder() . "Stock.yml", Config::YAML);//株の個数
    $this->price = new Config($this->getDataFolder() . "Price.yml", Config::YAML);//株の値段
    $this->company = new Config($this->getDataFolder() . "Company.yml", Config::YAML);//上場企業
    $this->pre = new Config($this->getDataFolder() . "Pre.yml", Config::YAML);//社長さん
    $this->amount = new Config($this->getDataFolder() . "Amount.yml", Config::YAML);//所持数
    if(!$this->amount->exists("Money")){
      $this->amount->set("Money", 0);
      $this->amount->save();
    }
  }

  public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
    if(strtolower($command->getName()) == "stock"){
      if(!isset($args[0])) return false;
      switch($args[0]){
       case "help":
        $sender->sendMessage("§a[About STOCKPLUGIN]\n§b/stock buy <会社名> <個数>§f株を買います。\n§b/stock  price <会社名>§f株の値段を確認します。\n§b/stock sell <会社名> <個数> §f株を売ります。\n§b/stock list <信頼のための額> <はじめの株の数> <会社名>§f上場します。\n§b/stock plus <追加株> §f株追加。\n§b/stock owner <会社名>§f株主を把握。\n§b/stock company §f上場企業を把握。");
        return true;
        break;

       case "buy":
       if(!isset($args[1])) return false;
       if(!isset($args[2])) return false;
       if(!(Int)$this->stock->get($args[1]) < (Int)$args[2]){
        $sender->sendMessage("§e[Error]企業の持つ株の上限を超えています。");
        return true;
        break;
       }
        $name = $sender->getName();
        if($this->price->exists($args[1])){
        $pr = $this->price->get($args[1]);
        $prize = $pr * (Int)$args[1];
        $this->EconomyAPI->reduceMoney($sender->getName(), $prize);
        $all = $this->company->getAll();
        $pre = $all[strtolower($args[1])]["社長"];
        $prr = $prize * 0.9;
        $this->EconomyAPI->addMoney($pre, +$prr);
        $pri = $prize * 0.1;
        $t = $this->amount->get("Money");
        $r = (Int)$t + $pri;
        $this->amount->set("Money", $r);
        $this->amount->save();
        $a = $this->stock->get($args[1]);
        $b = $a - (Int)$args[2];
        $this->stock->set($args[1], $b);
        $this->stock->save();
        $this->mine = new Config($this->getDataFolder() . "".$args[1].".yml", Config::YAML);
        if($this->mine->exists($sender->getName())){
        $c = $this->mine->get($sender->getName());
        $d = (Int)$c + (Int)$args[2];
        $this->mine->set($sender->getName(), $d);
        $this->mine->save();
      }else{
        $this->mine->set($sender->getName(), $args[2]);
        $this->mine->save();
      }
          $pre = $this->pre->get($name);
        $pri = $this->price->get($pre);
        $prr = $pri + (Int)$args[2] * 3;
        $this->price->set($pre, $prr);
        $this->price->save();
      $sender->sendMessage("§f[STOCK]§b購入が完了しました。");
      }else{
        $sender->sendMessage("§f[STOCK]§b未上場の企業です。");
      }
      return true;
        break;
        case "price":
        if(!isset($args[1])) return false;
        if($this->price->exists("".$args[1]."")){
        $pr = $this->price->get("".$args[1]."");
        $sender->sendMessage("§f[STOCK]§b ".$args[1]." : ".$pr."");
        }else{
        $sender->sendMessage("§f[STOCK]§b未上場の企業です。");
      }
      return true;
        break;
        case "sell":
        if(!isset($args[1])) return false;
        if(!isset($args[2])) return false;
        if($this->price->exists("".$args[1]."")){
        $this->min = new Config($this->getDataFolder() . "".$args[1].".yml", Config::YAML);
        if(!$this->min->exists($sender->getName())){
          $sender->sendMessage("§f[STOCK]§b株を持っていません。");
          return false;
          break;
        }
        $am = $this->min->get($sender->getName());
        if((Int)$args[2] > (Int)$am){
          $sender->sendMessage("§f[STOCK]§b指定株数を持っていません。");
        }
        $pr = $this->price->get("".$args[1]."");
        $price = $pr * (Int)$args[2];
        $a = $this->amount->get("Money");
        $b = (Int)$a + (Int)$price;
        $this->amount->set("Money", $b);
        $this->amount->save();
        $this->EconomyAPI->addMoney($sender->getName(), +$price);
        $pri = $this->price->get("".$args[1]."");
        $prr = $pri - (Int)$args[2] * 2;
        if($prr < 0){
          $this->price->set($pre, 1);
        $this->price->save();
        }else{
        $this->price->set($pre, $prr);
        $this->price->save();
      }
        $sender->sendMessage("§f[STOCK]§b売りました。");
      }else{
       $sender->sendMessage("§f[STOCK]§b未上場の企業です。");
      }
      return true;
        break;
        case "owner":
        if(!isset($args[1])) return false;
        $this->own = new Config($this->getDataFolder() . "".$args[1].".yml", Config::YAML);
        $all = $this->own->getAll(true);
        $al = implode(",", $all);
        $sender->sendMessage($al);
        return true;
        break;
        case "list":
        if(!isset($args[1])) return false;//上場時信頼してもらうために出すお金
        if(!isset($args[2])) return false;//はじめの株の個数
        if(!isset($args[3])) return false;//会社名
          if((Int)$args[1] < 1000){
          $sender->sendMessage("§f[STOCK]§b上場時信頼上昇の為の額は1000ドル以上必要です。");
          return true;
          break;
        }
          $money = $this->EconomyAPI->myMoney($sender->getName());
          if($money < 11000){
            $sender->sendMessage("§f[STOCK]§b上場の為の額は11000ドル以上必要です。");
            return false;
            break;
          }
        $pre = $sender->getName();
        $this->company->set(strtolower($args[3]),
        array("社長" => $pre));
        $this->company->save();
        $sender->sendMessage("§f[STOCK]§b経費10000ドル");
        $sender->sendMessage("§f[STOCK]§b内訳1 : 上場基本額8000ドル");
        $sender->sendMessage("§f[STOCK]§b内訳2 : 公募料金1000ドル");
        $sender->sendMessage("§f[STOCK]§b内訳3 : 株販売基本額1000ドル");
        $this->EconomyAPI->reduceMoney($sender->getName(), 10000);
        $a = $this->amount->get("Money");
        $c = (Int)$a + (Int)$args[1];
        $b = $c + 10000;
        $this->amount->set("Money", $b);
        $this->amount->save();
        $this->stock->set($args[3], (Int)$args[2]);
        $this->stock->save();
        $ab = "".$args[3]."";
        $this->newer = new Config($this->getDataFolder() . "".$ab.".yml", Config::YAML);
        $price = (Int)$args[1] / 20;
        $this->price->set($args[3], $price);
        $this->price->save();
        $this->pre->set($sender->getName(), $args[3]);
        $this->pre->save();
        $sender->sendMessage("§f[STOCK]§b上場が完了しました。");
        return true;
        break;
        case "plus":
        if(!isset($args[1])) return false;
        if($this->pre->exists($name)){
          $com = $this->pre->get($name);
          $prrr = (Int)$args[1] * 100;
          $sender->sendMessage("§f[STOCK]§b経費".$prrr."$");
        $sender->sendMessage("§f[STOCK]§b内訳1 : 株追加基本額".$prrr."ドル");
         $this->EconomyAPI->reduceMoney($sender->getName(), $prrr);
         $kosu = $this->stock->get($com);
         $k = (Int)$kosu + (Int)$args[1];
         $this->stock->set($com, $k);
         $this->stock->save();
        }else{
          $sender->sendMessage("§f[STOCK]§b社長以外不可です。");
        }
          return true;
          break;
          case "company":
     $max = 0;
        foreach($this->price->getAll(true) as $c){
        $max += count($c);
        }
        $max = ceil(($max / 5));
        $page = array_shift($args);
        $page = max(1, $page);
        $page = min($max, $page);
        $page = (int)$page;
        $sender->sendMessage("上場企業リスト".$page."/".$max." を表示");
        $clanlist = array_reverse($this->company->getAll(),true);
        $i = 0;
        foreach($clanlist as $b=>$a){
        if(($page - 1) * 5 <= $i && $i <= ($page - 1) * 5 + 4){
        $i1 = $i + 1;
        $sender->sendMessage("§a[".$i1."]§f".$b." / $".$this->price->get($c));
        }
        $i++;
        }
        if(count($args) >= 2){
        $max = 0;
        foreach($this->price->getAll(true) as $c){
        $max += count($c);
        }
        $max = ceil(($max / 5));
        $page = array_shift($args);
        $page = max(1, $page);
        $page = min($max, $page);
        $page = (int)$page;
        $sender->sendMessage("上場企業リスト".$page."/".$max." を表示");
        $clanlist = array_reverse($this->company->getAll(),true);
        $i = 0;
        foreach($clanlist as $b=>$a){
        if(($page - 1) * 5 <= $i && $i <= ($page - 1) * 5 + 4){
        $i1 = $i + 1;
        $sender->sendMessage("§a[".$i1."]§f".$b." / $".$this->price->get($c));
        }
        $i++;
        }
        }
        return true;
          break;
    }
  }
}
  public function onJoin(PlayerJoinEvent $e){
    $p = $e->getPlayer();
    $name = $p->getName();
    if($this->pre->exists($name)){
      $n = mt_rand(1,8);
      if($n == 3){
        $p->sendMessage("§f[STOCK]§b経費500ドル");
        $p->sendMessage("§f[STOCK]§b内訳1 : 会社運営額500ドル");
        $this->EconomyAPI->reduceMoney($name, 500);
        $a = $this->amount->get("Money");
        $c = (Int)$a + 1500;
        $this->amount->set("Money", $c);
        $this->amount->save();
        $pre = $this->pre->get($name);
        $pri = $this->price->get($pre);
        $prr = $pri + 15;
        $this->price->set($pre, $prr);
        $this->price->save();
      }
      if($n == 5){
        $pre = $this->pre->get($name);
        $pri = $this->price->get($pre);
        $prr = $pri - 10;
        $this->price->set($pre, $prr);
        $this->price->save();
        }
         $com = $this->pre->get($name);
         $pri = $this->price->get($pre);
         if($pri = 1){
          $p->sendMessage("§f[STOCK]§bあなたの会社の株価は1ドルに達しました。");
          $p->sendMessage("§f[STOCK]§bあなたの会社".$com."は倒産しました。");
          $this->pre->remove($name);
          $this->pre->save();
          $this->price->remove($com);
          $this->price->save();
          $this->company->remove($com);
          $this->company->save();
          $this->stock->remove($com);
          $this->stock->save();
          }
          if($pri < 10 and 1 < $pri){
            $p->sendMessage("§f[STOCK]§bあなたの会社の株価は10ドル未満です！");
            $p->sendMessage("§f[STOCK]§b株価が1ドルとなると倒産となります。");
            $p->sendMessage("§f[STOCK]§bあなたの会社".$com."は倒産の危機に有ります。");
          }
    }
  }
}
