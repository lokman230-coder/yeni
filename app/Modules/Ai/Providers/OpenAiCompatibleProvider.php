<?php
declare(strict_types=1);
namespace App\Modules\Ai\Providers;
use App\Modules\Ai\Contracts\AiProviderInterface;
use App\Services\Logger\ApiLogger;
final class OpenAiCompatibleProvider implements AiProviderInterface {
    public function __construct(private string $id, private string $apiKey, private string $endpoint, private string $model) {}
    public function id(): string { return $this->id; }
    public function chat(array $messages,array $options=[]): array {
        if($this->apiKey==='') return ['content'=>'','tokens'=>0,'provider'=>$this->id,'error'=>'API key tanımlı değil'];
        $payload=['model'=>$options['model']??$this->model,'messages'=>$messages,'temperature'=>$options['temperature']??0.4,'max_tokens'=>$options['max_tokens']??800];
        $ch=curl_init($this->endpoint); curl_setopt_array($ch,[CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$this->apiKey],CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>true]); $start=microtime(true); $raw=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch); $data=$raw?json_decode((string)$raw,true):[]; $duration=(int)((microtime(true)-$start)*1000); ApiLogger::log($this->id,'chat/completions','POST',['model'=>$this->model],['choices_count'=>count($data['choices']??[])],$code,$duration,$err?:null);
        if($raw===false||$code>=400) return ['content'=>'','tokens'=>0,'provider'=>$this->id,'error'=>$err?:'HTTP '.$code.' - '.($data['error']['message']??'')];
        return ['content'=>$data['choices'][0]['message']['content']??'','tokens'=>(int)($data['usage']['total_tokens']??0),'provider'=>$this->id,'model'=>$this->model];
    }
}
