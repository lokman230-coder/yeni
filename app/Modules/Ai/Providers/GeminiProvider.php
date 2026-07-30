<?php
declare(strict_types=1);
namespace App\Modules\Ai\Providers;
use App\Modules\Ai\Contracts\AiProviderInterface;
use App\Services\Logger\ApiLogger;
final class GeminiProvider implements AiProviderInterface {
    public function __construct(private string $apiKey, private string $model='gemini-2.0-flash') {}
    public function id(): string { return 'gemini'; }
    public function chat(array $messages,array $options=[]): array {
        if($this->apiKey==='') return ['content'=>'','tokens'=>0,'provider'=>'gemini','error'=>'Gemini API key tanımlı değil'];
        $contents=[]; foreach($messages as $m){$contents[]=['role'=>($m['role']??'user')==='assistant'?'model':'user','parts'=>[['text'=>(string)($m['content']??'')]]];}
        $url='https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($options['model']??$this->model).':generateContent?key='.rawurlencode($this->apiKey);
        $payload=['contents'=>$contents,'generationConfig'=>['temperature'=>$options['temperature']??0.4,'maxOutputTokens'=>$options['max_tokens']??800]];
        $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>true]); $start=microtime(true); $raw=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch); $data=$raw?json_decode((string)$raw,true):[]; $duration=(int)((microtime(true)-$start)*1000); ApiLogger::log('gemini','generateContent','POST',['model'=>$this->model,'msg_count'=>count($messages)],['candidates'=>count($data['candidates']??[])],$code,$duration,$err?:null);
        if($raw===false||$code>=400) return ['content'=>'','tokens'=>0,'provider'=>'gemini','error'=>$err?:'HTTP '.$code.' - '.($data['error']['message']??'')];
        $parts=$data['candidates'][0]['content']['parts']??[]; $text=''; foreach($parts as $p)$text.=(string)($p['text']??''); return ['content'=>$text,'tokens'=>(int)($data['usageMetadata']['totalTokenCount']??0),'provider'=>'gemini','model'=>$this->model];
    }
}
