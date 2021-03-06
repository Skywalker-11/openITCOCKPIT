<?php
// Copyright (C) <2018>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

namespace itnovum\openITCOCKPIT\Perfdata;


use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\PerfdataBackend;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Graphite\GraphiteConfig;
use itnovum\openITCOCKPIT\Graphite\GraphiteLoader;
use itnovum\openITCOCKPIT\Graphite\GraphiteMetric;
use Statusengine\PerfdataParser;

/**
 * Class PerfdataLoader
 * @package itnovum\openITCOCKPIT\Perfdata
 * @property \Rrd $Rrd
 * @property \Servicestatus $Servicestatus
 */
class PerfdataLoader {

    const REDUCE_METHOD_STEPS = 1;
    const REDUCE_METHOD_AVERAGE = 2;
    const MAX_RESPONSE_GRAPH_POINTS = 1000;

    /**
     * @var DbBackend
     */
    private $DbBackend;

    /**
     * @var PerfdataBackend
     */
    private $PerfdataBackend;

    /**
     * @var \Model
     */
    private $Servicestatus;

    /**
     * @var \Model
     */
    private $Rrd;

    /**
     * PerfdataLoader constructor.
     * @param DbBackend $DbBackend
     * @param PerfdataBackend $PerfdataBackend
     * @param \Model $Servicestatus
     * @param \Model $Rrd
     * @param null|string $gauge
     */
    public function __construct(DbBackend $DbBackend, PerfdataBackend $PerfdataBackend, \Model $Servicestatus, \Model $Rrd) {
        $this->DbBackend = $DbBackend;
        $this->PerfdataBackend = $PerfdataBackend;
        $this->Servicestatus = $Servicestatus;
        $this->Rrd = $Rrd;
    }

    /**
     * @param $hostUuid
     * @param $serviceUuid
     * @param $start
     * @param $end
     * @param bool $jsTimestamp
     * @param string $type
     * @param null|string $gauge
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPerfdataByUuid($hostUuid, $serviceUuid, $start, $end, $jsTimestamp = false, $type = 'avg', $gauge = null) {
        if ($gauge === '') {
            $gauge = null;
        }

        $performance_data = [];

        if ($this->PerfdataBackend->isWhisper()) {
            $ServicestatusFields = new ServicestatusFields($this->DbBackend);
            $ServicestatusFields->perfdata();
            $servicestatus = $this->Servicestatus->byUuid($serviceUuid, $ServicestatusFields);
            if (!empty($servicestatus)) {
                $PerfdataParser = new PerfdataParser($servicestatus['Servicestatus']['perfdata']);
                $perfdataMetadata = $PerfdataParser->parse();

                $GraphiteConfig = new GraphiteConfig();
                $GraphiteLoader = new GraphiteLoader($GraphiteConfig);
                $GraphiteLoader->setUseJsTimestamp($jsTimestamp);
                $GraphiteLoader->setAbsoluteDate($start, $end);

                foreach ($perfdataMetadata as $metricName => $metric) {
                    if ($gauge !== null) {
                        if ($gauge !== $metricName) {
                            continue;
                        }
                    }

                    $GraphiteMetric = new GraphiteMetric(
                        $hostUuid,
                        $serviceUuid,
                        $metricName
                    );

                    $datasource = [
                        'ds'    => $metricName,
                        'name'  => $metricName,
                        'label' => $metricName,
                        'unit'  => $metric['unit'],
                        'act'   => $metric['current'],
                        'warn'  => $metric['warning'],
                        'crit'  => $metric['critical'],
                        'min'   => $metric['min'],
                        'max'   => $metric['max'],
                    ];

                    switch ($type) {
                        case 'min':
                            $performance_data[] = [
                                'datasource' => $datasource,
                                'data'       => $GraphiteLoader->getSeriesMin($GraphiteMetric)
                            ];
                            break;

                        case 'max':
                            $performance_data[] = [
                                'datasource' => $datasource,
                                'data'       => $GraphiteLoader->getSeriesMax($GraphiteMetric)
                            ];
                            break;

                        default:
                            $performance_data[] = [
                                'datasource' => $datasource,
                                'data'       => $GraphiteLoader->getSeriesAvg($GraphiteMetric)
                            ];
                            break;
                    }

                }
            }
        }

        if ($this->PerfdataBackend->isRrdtool()) {
            if (file_exists(sprintf('/opt/openitc/nagios/share/perfdata/%s/%s.rrd', $hostUuid, $serviceUuid))) {
                $options = [
                    'start' => $start,
                    'end'   => $end
                ];

                $rrd_data = $this->Rrd->getPerfDataFiles($hostUuid, $serviceUuid, $options, null, $type);

                $limit = (int)self::MAX_RESPONSE_GRAPH_POINTS / sizeof($rrd_data['data']);
                foreach ($rrd_data['xml_data'] as $dataSource) {
                    if ($gauge !== null) {
                        if ($gauge !== $dataSource['name']) {
                            continue;
                        }
                    }

                    $tmpData = $this->Rrd->reduceData($rrd_data['data'][$dataSource['ds']], $limit, self::REDUCE_METHOD_AVERAGE);
                    $data = [];

                    foreach ($tmpData as $timestamp => $value) {
                        if ($value !== null) {
                            if ($jsTimestamp) {
                                $data[($timestamp * 1000)] = $value;
                            } else {
                                $data[$timestamp] = $value;
                            }
                        }
                    }

                    $performance_data[] = [
                        'datasource' => $dataSource,
                        'data'       => $data
                    ];
                }
            }
        }

        return $performance_data;
    }

}
