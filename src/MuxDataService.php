<?php

namespace Jacotheron\LaravelMux;

use GuzzleHttp\Client;
use MuxPhp\Api\DimensionsApi;
use MuxPhp\Api\ErrorsApi;
use MuxPhp\Api\ExportsApi;
use MuxPhp\Api\FiltersApi;
use MuxPhp\Api\IncidentsApi;
use MuxPhp\Api\MetricsApi;
use MuxPhp\Api\RealTimeApi;
use MuxPhp\Api\VideoViewsApi;
use MuxPhp\Configuration;

class MuxDataService
{
    private Configuration $config;
    private DimensionsApi $dimensionsApi;
    private ErrorsApi $errorsApi;
    private ExportsApi $exportsApi;
    private FiltersApi $filtersApi;
    private IncidentsApi $incidentsApi;
    private MetricsApi $metricsApi;
    private RealTimeApi $realTimeApi;
    private VideoViewsApi $videoViewsApi;
    private $result;

    public function __construct(){
        $this->config = Configuration::getDefaultConfiguration()
            ->setUsername(config('laravel-mux.authentication.mux_token_id'))
            ->setPassword(config('laravel-mux.authentication.mux_token_secret'));

        $http_client = new Client();
        $this->dimensionsApi = new DimensionsApi($http_client, $this->config);
        $this->errorsApi = new ErrorsApi($http_client, $this->config);
        $this->exportsApi = new ExportsApi($http_client, $this->config);
        $this->filtersApi = new FiltersApi($http_client, $this->config);
        $this->incidentsApi = new IncidentsApi($http_client, $this->config);
        $this->metricsApi = new MetricsApi($http_client, $this->config);
        $this->realTimeApi = new RealTimeApi($http_client, $this->config);
        $this->videoViewsApi = new VideoViewsApi($http_client, $this->config);
    }

    public function dimensions(string $action = 'list-filters', array $options = []){
        switch($action){
            case 'list-filters':
                $this->result = $this->dimensionsApi->listDimensions();
                break;
            case 'list-dimension-values':
                $this->result = $this->dimensionsApi->listDimensionValues($options['dimension'],
                    $options['limit'] ?? 25, $options['page'] ?? 1, $options['filters'] ?? null,
                    $options['timeframe'] ?? null);
                break;
        }
    }

    public function errors(string $action = 'list-errors', array $options = []){
        switch($action){
            case 'list-errors':
                $this->result = $this->errorsApi->listErrors($options['filters'] ?? null, $options['timeframe'] ?? null);
                break;
        }
    }

    public function exports(string $action = 'list-exports', array $options = []){
        switch($action){
            case 'list-exports':
                $this->result = $this->exportsApi->listExports();
                break;
        }
    }

    public function filters(string $action = 'list-filters', array $options = []){
        switch($action){
            case 'list-filters':
                $this->result = $this->filtersApi->listFilters();
                break;
            case 'list-filter-values':
                $this->result = $this->filtersApi->listFilterValues($options['filter_id'], $options['limit'] ?? 25,
                    $options['page'] ?? 1, $options['filters'] ?? null, $options['timeframe'] ?? null);
                break;
        }
    }

    public function incidents(string $action = 'list-incidents', array $options = []){
        switch($action){
            case 'list-incidents':
                $this->result = $this->incidentsApi->listIncidents($options['limit'] ?? 25, $options['page'] ?? 1,
                $options['order_by'] ?? null, $options['order_direction'] ?? null, $options['status'] ?? null,
                $options['severity'] ?? null);
                break;
            case 'get-incident':
                $this->result = $this->incidentsApi->getIncident($options['incident_id']);
                break;
            case 'list-related-incidents':
                $this->result = $this->incidentsApi->listRelatedIncidents($options['incident_id'], $options['limit'] ?? 25,
                    $options['page'] ?? 1, $options['order_by'] ?? null, $options['order_direction'] ?? null);
                break;
        }
    }

    public function metrics(string $action = 'list-metrics', array $options = []){
        switch($action){
            case 'list-breakdown-values':
                $this->result = $this->metricsApi->listBreakdownValues($options['metric_id'], $options['group_by'] ?? null,
                $options['measurement'] ?? null, $options['filters'] ?? null, $options['limit'] ?? 25,
                $options['page'] ?? 1, $options['order_by'] ?? null, $options['order_direction'] ?? null,
                $options['timeframe'] ?? null);
                break;
            case 'get-overall-values':
                $this->result = $this->metricsApi->getOverallValues($options['metric_id'], $options['timeframe'] ?? null,
                $options['filters'] ?? null, $options['measurement'] ?? null);
                break;
            case 'list-insights':
                $this->result = $this->metricsApi->listInsights($options['metric_id'], $options['measurement'] ?? null,
                $options['order_direction'] ?? null, $options['timeframe'] ?? null);
                break;
            case 'get-metric-timeseries-data':
                $this->result = $this->metricsApi->getMetricTimeseriesData($options['metric_id'], $options['timeframe'] ?? null,
                $options['filters'] ?? null, $options['measurement'] ?? null, $options['order_direction'] ?? null,
                $options['group_by'] ?? null);
                break;
            case 'list-all-metric-values':
                $this->result = $this->metricsApi->listAllMetricValues($options['metric_id'], $options['timeframe'] ?? null,
                $options['dimension'] ?? null, $options['value'] ?? null);
                break;
        }
    }

    public function realtime(string $action = 'list-realtime-dimensions', array $options = []){
        switch($action){
            case 'list-realtime-dimensions':
                $this->result = $this->realTimeApi->listRealtimeDimensions();
                break;
            case 'list-realtime-metrics':
                $this->result = $this->realTimeApi->listRealtimeMetrics();
                break;
            case 'get-realtime-breakdown':
                $this->result = $this->realTimeApi->getRealtimeBreakdown($options['realtime_metric_id'], $options['dimension'] ?? null,
                $options['timestamp'] ?? null, $options['filters'] ?? null, $options['order_by'] ?? null,
                $options['order_directions'] ?? null);
                break;
            case 'get-realtime-histogram':
                $this->result = $this->realTimeApi->getRealtimeHistogramTimeseries($options['realtime_histogram_metric_id'], $options['filters'] ?? null);
                break;
            case 'get-realtime-timeseries':
                $this->result = $this->realTimeApi->getRealtimeTimeseries($options['realtime_histogram_metric_id'], $options['filters'] ?? null);
                break;
        }
    }

    public function views(string $action = 'list-video-views', array $options = []){
        switch($action){
            case 'list-video-views':
                $this->result = $this->videoViewsApi->listVideoViews($options['limit'] ?? 25, $options['page'] ?? 1,
                $options['viewer_id'] ?? null, $options['error_id'] ?? null, $options['order_direction'],
                $options['filters'] ?? null, $options['timeframe'] ?? null);
                break;
            case 'get-video-view':
                $this->result = $this->videoViewsApi->getVideoView($options['video_view_id']);
                break;
        }
    }

    //results
    public function getResultData(){
        if(is_bool($this->result)){
            return $this->result;
        }
        return $this->result->getData();
    }
}