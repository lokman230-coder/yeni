<?php
declare(strict_types=1);
namespace App\Modules\Ai\Providers;
use App\Modules\Ai\Contracts\AiProviderInterface;
use App\Services\Logger\ApiLogger;
final class ClaudeProvider implements AiProviderInterface {
    public function __construct(private string $apiKey, private string $model='claude-3-5-sonnet-latest') {}
    public function id(): string { return 'claude'; }
    public function chat(array $messages,array $options=[]): array {
        if($this->apiKey==='') return ['content'=>'','tokens'=>0,'provider'=>'claude','error'=>'Claude API key tanımlı değil'];
        $system=''; $body=[]; foreach($messages as $m){if(($m['role']??'user')==='system'){$system.=(string)$m['content'];}else{$body[]=['role'=>($m['role']??'user')==='assistant'?'assistant':'user','content'=>(string)($m['content']??'')];}}
        $payload=['model'=>$options['model']??$this->model,'max_tokens'=>$options['max_tokens']??800,'temperature'=>$options['temperature']??0.4,'messages'=>$body]; if($system!=='')$payload['system']=$system;
        $ch=curl_init('https://api.anthropic.com/v1/messages'); curl_setopt_array($ch,[CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$this->apiKey,'anthropic-version: 2023-06-01'],CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>true]); $start=microtime(true); $raw=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch); $data=$raw?json_decode((string)$raw,true):[]; $duration=(int)((microtime(true)-$start)*1000); ApiLogger::log('claude','messages','POST',['model'=>$this->model],['content_blocks'=>count($data['content']??[])],$code,$duration,$err?:null);
        if($raw===false||$code>=400)return ['content'=>'','tokens'=>0,'provider'=>'claude','error'=>$err?:'HTTP '.$code.' - '.($data['error']['message']??'')]; $text=''; foreach(($data['content']??[]) as $p)$text.=(string)($p['text']??''); return ['content'=>$text,'tokens'=>(int)($data['usage']['input_tokens']??0)+(int)($data['usage']['output_tokens']??0),'provider'=>'claude','model'=>$this->model];
    }
}
