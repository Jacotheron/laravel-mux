<?php

namespace Jacotheron\LaravelMux;

use GuzzleHttp\Client;
use MuxPhp\Api\AssetsApi;
use MuxPhp\Api\DeliveryUsageApi;
use MuxPhp\Api\DirectUploadsApi;
use MuxPhp\Api\LiveStreamsApi;
use MuxPhp\Api\PlaybackIDApi;
use MuxPhp\Api\URLSigningKeysApi;
use MuxPhp\Configuration;
use MuxPhp\Models\CreateAssetRequest;
use MuxPhp\Models\CreateLiveStreamRequest;
use MuxPhp\Models\CreatePlaybackIDRequest;
use MuxPhp\Models\CreateSimulcastTargetRequest;
use MuxPhp\Models\CreateTrackRequest;
use MuxPhp\Models\CreateUploadRequest;
use MuxPhp\Models\InputSettings;
use MuxPhp\Models\UpdateAssetMasterAccessRequest;
use MuxPhp\Models\UpdateAssetMP4SupportRequest;

class MuxService
{
    private Configuration $config;
    private AssetsApi $assetsApi;
    private DirectUploadsApi $directUploadsApi;
    private URLSigningKeysApi $urlSingingKeyApi;
    private LiveStreamsApi $liveStreamsApi;
    private PlaybackIDApi $playbackIDApi;
    private DeliveryUsageApi $deliveryUsageApi;
    private InputSettings $input;
    private array $captioninput = [];
    private $result;
    private $default_playback_policy;

    public function __construct(){
        $this->config = Configuration::getDefaultConfiguration()
            ->setUsername(config('laravel-mux.authentication.mux_token_id'))
            ->setPassword(config('laravel-mux.authentication.mux_token_secret'));

        $http_client = new Client();
        $this->assetsApi = new AssetsApi($http_client, $this->config);
        $this->directUploadsApi = new DirectUploadsApi($http_client, $this->config);
        $this->urlSingingKeyApi = new URLSigningKeysApi($http_client, $this->config);
        $this->liveStreamsApi = new LiveStreamsApi($http_client, $this->config);
        $this->playbackIDApi = new PlaybackIDApi($http_client, $this->config);
        $this->deliveryUsageApi = new DeliveryUsageApi($http_client, $this->config);
        $this->default_playback_policy = config('laravel-mux.default_playback_policy');
    }

    public function setInput(array $input){
        $this->input = new InputSettings($input);
        return $this;
    }

    public function setCaptionsInput(array $input){
        $this->captioninput[] = new InputSettings($input);
        return $this;
    }

    //Requests
    public function createAssetRequest(array|null $playback_policy = null, bool $execute = true){
        $options = ['playback_policy' => $playback_policy ?? $this->default_playback_policy];
        if($this->input && !empty($this->captioninput)){
            $options['input'] = [
                $this->input,
            ];
            foreach($this->captioninput as $caption){
                $options['input'][] = $caption;
            }
        }elseif($this->input){
            $options['input'] = $this->input;
        }
        $request = new CreateAssetRequest($options);
        if($execute){
            $this->result = $this->assetsApi->createAsset($request);
            return $this;
        }
        return $request;
    }

    public function directUploadsRequest($action = 'create', array $options = []){
        switch ($action){
            case 'create':
                $createAssetRequest = $this->createAssetRequest($options['playback_policy'] ?? $this->default_playback_policy, false);
                $options['new_asset_settings'] = $createAssetRequest;
                $options['cors_origin'] = config('app.url');
                $request = new CreateUploadRequest($options);
                $this->result = $this->directUploadsApi->createDirectUpload($request);
                break;
            case 'list':
                $this->result = $this->directUploadsApi->listDirectUploads($options['limit'] ?? 25, $options['page'] ?? 1);
                break;
            case 'get':
                $this->result = $this->directUploadsApi->getDirectUpload($options['upload_id']);
                break;
            case 'cancel':
                $this->result = $this->directUploadsApi->cancelDirectUpload($options['upload_id']);
                break;
        }
        return $this;
    }

    public function assetsManagement($action = 'list', array $options = []){
        switch($action){
            case'list':
                $this->result = $this->assetsApi->listAssets($options['limit'] ?? 25, $options['page'] ?? 1, $options['stream_id'] ?? null, $options['upload_id'] ?? null);
                break;
            case 'create':
                $this->createAssetRequest($options['playback_policy'] ?? null, $options['execute'] ?? true);
                break;
            case 'getAsset':
                $this->result = $this->assetsApi->getAsset($options['asset_id']);
                break;
            case 'wait-for-ready':
                $readyRequest = $this->assetsApi->getAsset($options['asset_id']);
                if($readyRequest->getData()->getStatus() !== 'ready'){
                    while(true){
                        $readyRequest = $this->assetsApi->getAsset($options['asset_id']);
                        if($readyRequest->getData()->getStatus() === 'ready'){
                            $this->result = $readyRequest;
                            break;
                        }
                        sleep(5);
                    }
                }
                break;
            case 'asset-input-info':
                $this->result = $this->assetsApi->getAssetInputInfo($options['asset_id']);
                break;

            case 'asset-clipping':
                $this->input = new InputSettings([$options['clipping_options']]);
                $this->createAssetRequest($options['playback_policy'] ?? null, $options['execute'] ?? true);
                break;
            case 'asset-create-playback-id':
                $request = new CreatePlaybackIDRequest(['policy' => $options['playback_policy'] ?? $this->default_playback_policy]);
                $this->result = $this->assetsApi->createAssetPlaybackId($options['asset_id'], $request);
                break;
            case 'asset-get-playback-id':
                $this->result = $this->assetsApi->getAssetPlaybackId($options['asset_id'], $options['playback_id']);
                break;
            case 'asset-delete-playback-id':
                $this->result = $this->assetsApi->deleteAssetPlaybackId($options['asset_id'], $options['playback_id'])?? true;
                break;
            case 'asset-mp4-support':
                $request = new UpdateAssetMP4SupportRequest(['mp4_support' => $options['mp4_support']]);
                $this->result = $this->assetsApi->updateAssetMp4Support($options['asset_id'], $request);
                break;
            case 'asset-master-access':
                $request = new UpdateAssetMasterAccessRequest(['master_access' => $options['master_access']]);
                $this->result = $this->assetsApi->updateAssetMasterAccess($options['asset_id'], $request);
                break;
            case 'asset-add-captions':
            case 'asset-create-track':
                $request = new CreateTrackRequest($options['track_request']);
                $this->result = $this->assetsApi->createAssetTrack($options['asset_id'], $request);
                break;
            case 'asset-delete-captions':
            case 'asset-delete-track':
                $this->result = $this->assetsApi->deleteAssetTrack($options['asset_id'], $options['track_id']) ?? true;
                break;
            case 'asset-delete':
                $this->result = $this->assetsApi->deleteAsset($options['asset_id']) ?? true;
                break;
        }
        return $this;
    }

    public function liveStreams($action = 'create', array $options = []){
        switch ($action){
            case 'create':
                $create_asset_request = $this->createAssetRequest($options['playback_policy'] ?? $this->default_playback_policy, false);
                $create_livestream_request = new CreateLiveStreamRequest([
                    'playback_policy' => $options['playback_policy'] ?? $this->default_playback_policy,
                    'new_asset_settings' => $create_asset_request,
                    'reduced_latency' => $options['reduced_latency'] ?? false
                ]);
                $this->result = $this->liveStreamsApi->createLiveStream($create_livestream_request);
                break;
            case 'list':
                $this->result = $this->liveStreamsApi->listLiveStreams($options['limit'] ?? 25, $options['page'] ?? 1, $options['stream_key'] ?? null, $options['status'] ?? null);
                break;
            case 'get':
                $this->result = $this->liveStreamsApi->getLiveStream($options['livestream_id']);
                break;
            case 'get-playback-id':
                $this->result = $this->playbackIDApi->getAssetOrLivestreamId($options['livestream_id']);
                break;

            case 'create-simulcast':
                $create_target = new CreateSimulcastTargetRequest($options['simulcast_target']);
                $this->result = $this->liveStreamsApi->createLiveStreamSimulcastTarget($options['livestream_id'], $create_target);
                break;
            case 'get-simulcast-target':
                $this->result = $this->liveStreamsApi->getLiveStreamSimulcastTarget($options['livestream_id'], $options['target_id']);
                break;
            case 'delete-simulcast-target':
                $this->liveStreamsApi->deleteLiveStreamSimulcastTarget($options['livestream_id'], $options['target_id']);
                $this->result = true;
                break;

            case 'create-livestream-playback-id':
                $request = new CreatePlaybackIDRequest(['policy' => $options['playback_policy'] ?? $this->default_playback_policy]);
                $this->result = $this->liveStreamsApi->createLiveStreamPlaybackId($options['livestream_id'], $request);
                break;
            case 'delete-livestream-playback-id':
                $this->liveStreamsApi->deleteLiveStreamPlaybackId($options['livestream_id'], $options['playback_id']);
                $this->result = true;
                break;

            case 'reset-stream-key':
                $this->result = $this->liveStreamsApi->resetStreamKey($options['livestream_id']);
                break;

            case 'stream-complete-signal':
                $this->result = $this->liveStreamsApi->signalLiveStreamComplete($options['livestream_id']);
                break;

            case 'stream-disable':
                $this->result = $this->liveStreamsApi->disableLiveStream($options['livestream_id']);
                break;

            case 'stream-enable':
                $this->result = $this->liveStreamsApi->enableLiveStream($options['livestream_id']);
                break;

            case 'stream-delete':
                $this->result = $this->liveStreamsApi->deleteLiveStream($options['livestream_id']) ?? true;
                break;
        }
        return $this;
    }

    public function getDeliveryUsage(int $start = null, int $end = null, array $options = []){
        $this->result = $this->deliveryUsageApi->listDeliveryUsage(
            $options['page'] ?? 1,
            $options['limit'] ?? 100,
            $options['asset_id'] ?? null,
            $options['livestream_id'] ?? null,
            [$start, $end]
            );
    }

    public function signing(string $action = '', array $options = []){
        switch($action){
            case 'create-key':
                $this->result = $this->urlSingingKeyApi->createUrlSigningKey();
                break;
            case 'list-keys':
                $this->result = $this->urlSingingKeyApi->listUrlSigningKeys($options['limit'] ?? 25, $options['page'] ?? 1);
                break;
            case 'get-key':
                $this->result = $this->urlSingingKeyApi->getUrlSigningKey($options['key_id']);
                break;
            case 'delete-key':
                $this->result = $this->urlSingingKeyApi->deleteUrlSigningKey($options['key_id']) ?? true;
                break;
        }
        return $this;
    }

    //results
    public function getResultData(){
        if(is_bool($this->result)){
            return $this->result;
        }
        return $this->result->getData();
    }
}