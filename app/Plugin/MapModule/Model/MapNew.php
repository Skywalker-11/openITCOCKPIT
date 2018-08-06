<?php
// Copyright (C) <2015>  <it-novum GmbH>
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

use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\MapConditions;
use itnovum\openITCOCKPIT\Core\ServicestatusConditions;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use Statusengine\PerfdataParser;

class MapNew extends MapModuleAppModel {

    public $useTable = false;

    private $hostIcons = [
        0 => 'up.png',
        1 => 'down.png',
        2 => 'unreachable.png'
    ];
    private $serviceIcons = [
        0 => 'up.png',
        1 => 'warning.png',
        2 => 'critical.png',
        3 => 'unknown.png'
    ];
    private $ackIcon = 'ack.png';
    private $downtimeIcon = 'downtime.png';
    private $ackAndDowntimeIcon = 'downtime_ack.png';

    private $errorIcon = 'error.png';

    /**
     * @param Model $Service
     * @param Model $Hoststatus
     * @param Model $Servicestatus
     * @param $host
     * @return array
     */
    public function getHostInformation(Model $Service, Model $Hoststatus, Model $Servicestatus, $host) {
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
        $hoststatus = $Hoststatus->byUuid($host['Host']['uuid'], $HoststatusFields);

        $HostView = new \itnovum\openITCOCKPIT\Core\Views\Host($host);

        if (empty($hoststatus)) {
            $HoststatusView = new \itnovum\openITCOCKPIT\Core\Hoststatus([]);
            return [
                'icon' => $this->errorIcon,
                'icon_property' => $this->errorIcon,
                'isAcknowledged' => false,
                'isInDowntime' => false,
                'color' => 'text-primary',
                'background' => 'bg-color-blueLight',
                'Host' => $HostView->toArray(),
                'Hoststatus' => $HoststatusView->toArray(),
            ];
        }

        $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($hoststatus['Hoststatus']);
        $icon = $this->hostIcons[$hoststatus->currentState()];
        $color = $hoststatus->HostStatusColor();
        $background = $hoststatus->HostStatusBackgroundColor();

        $iconProperty = $icon;
        if ($hoststatus->isAcknowledged()) {
            $iconProperty = $this->ackIcon;
        }

        if ($hoststatus->isInDowntime()) {
            $iconProperty = $this->downtimeIcon;
        }

        if ($hoststatus->isAcknowledged() && $hoststatus->isInDowntime()) {
            $iconProperty = $this->ackAndDowntimeIcon;
        }

        if ($hoststatus->currentState() > 0) {
            return [
                'icon' => $icon,
                'icon_property' => $this->errorIcon,
                'isAcknowledged' => $hoststatus->isAcknowledged(),
                'isInDowntime' => $hoststatus->isInDowntime(),
                'color' => $color,
                'background' => $background,
                'Host' => $HostView->toArray(),
                'Hoststatus' => $hoststatus->toArray(),
            ];
        }

        //Check services for cumulated state (only if host is up)
        $services = $Service->find('list', [
            'recursive' => -1,
            'fields' => [
                'Service.uuid'
            ],
            'conditions' => [
                'Service.host_id' => $host['Host']['id'],
                'Service.disabled' => 0
            ]
        ]);

        $ServicestatusFieds = new ServicestatusFields($this->DbBackend);
        $ServicestatusFieds->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
        $ServicestatusConditions = new ServicestatusConditions($this->DbBackend);
        $ServicestatusConditions->servicesWarningCriticalAndUnknown();
        $servicestatus = $Servicestatus->byUuid($services, $ServicestatusFieds, $ServicestatusConditions);

        if (!empty($servicestatus)) {
            $worstServiceState = array_values(
                Hash::sort($servicestatus, '{s}.Servicestatus.current_state', 'desc')
            );

            $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($worstServiceState[0]['Servicestatus']);
            $serviceIcon = $this->serviceIcons[$servicestatus->currentState()];

            $serviceIconProperty = $serviceIcon;
            if ($servicestatus->isAcknowledged()) {
                $serviceIconProperty = $this->ackIcon;
            }

            if ($servicestatus->isInDowntime()) {
                $serviceIconProperty = $this->downtimeIcon;
            }

            if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
                $serviceIconProperty = $this->ackAndDowntimeIcon;
            }

            return [
                'icon' => $serviceIcon,
                'icon_property' => $serviceIconProperty,
                'isAcknowledged' => $servicestatus->isAcknowledged(),
                'isInDowntime' => $servicestatus->isInDowntime(),
                'color' => $servicestatus->ServiceStatusColor(),
                'background' => $servicestatus->ServiceStatusBackgroundColor(),
                'Host' => $HostView->toArray(),
                'Hoststatus' => $hoststatus->toArray(),
            ];
        }

        return [
            'icon' => $icon,
            'icon_property' => $iconProperty,
            'isAcknowledged' => $hoststatus->isAcknowledged(),
            'isInDowntime' => $hoststatus->isInDowntime(),
            'color' => $color,
            'background' => $background,
            'Host' => $HostView->toArray(),
            'Hoststatus' => $hoststatus->toArray()
        ];
    }

    /**
     * @param Model $Servicestatus
     * @param $service
     * @return array
     */
    public function getServiceInformation(Model $Servicestatus, $service) {
        $ServicestatusFields = new ServicestatusFields($this->DbBackend);
        $ServicestatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged()->perfdata()->isFlapping();
        $servicestatus = $Servicestatus->byUuid($service['Service']['uuid'], $ServicestatusFields);
        $HostView = new \itnovum\openITCOCKPIT\Core\Views\Host($service);
        $ServiceView = new \itnovum\openITCOCKPIT\Core\Views\Service($service);
        if (empty($servicestatus)) {
            $ServicestatusView = new \itnovum\openITCOCKPIT\Core\Servicestatus([]);
            return [
                'icon' => $this->errorIcon,
                'icon_property' => $this->errorIcon,
                'isAcknowledged' => false,
                'isInDowntime' => false,
                'color' => 'text-primary',
                'background' => 'bg-color-blueLight',
                'Host' => $HostView->toArray(),
                'Service' => $ServiceView->toArray(),
                'Servicestatus' => $ServicestatusView->toArray(),
                'Perfdata' => []
            ];
        }

        $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($servicestatus['Servicestatus']);

        $icon = $this->serviceIcons[$servicestatus->currentState()];

        $iconProperty = $icon;
        if ($servicestatus->isAcknowledged()) {
            $iconProperty = $this->ackIcon;
        }

        if ($servicestatus->isInDowntime()) {
            $iconProperty = $this->downtimeIcon;
        }

        if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
            $iconProperty = $this->ackAndDowntimeIcon;
        }

        $perfdata = new PerfdataParser($servicestatus->getPerfdata());


        return [
            'icon' => $icon,
            'icon_property' => $iconProperty,
            'isAcknowledged' => $servicestatus->isAcknowledged(),
            'isInDowntime' => $servicestatus->isInDowntime(),
            'color' => $servicestatus->ServiceStatusColor(),
            'background' => $servicestatus->ServiceStatusBackgroundColor(),
            'Host' => $HostView->toArray(),
            'Service' => $ServiceView->toArray(),
            'Perfdata' => $perfdata->parse(),
            'Servicestatus' => $servicestatus->toArray()
        ];
    }

    /**
     * @param Model $Service
     * @param Model $Hoststatus
     * @param Model $Servicestatus
     * @param $hostgroup
     * @return array
     */
    public function getHostgroupInformation(Model $Service, Model $Hoststatus, Model $Servicestatus, $hostgroup) {
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();

        $hostUuids = Hash::extract($hostgroup['Host'], '{n}.uuid');

        $hoststatusByUuids = $Hoststatus->byUuid($hostUuids, $HoststatusFields);

        $hostgroupLight = [
            'id' => (int)$hostgroup['Hostgroup']['id'],
            'name' => $hostgroup['Container']['name'],
            'description' => $hostgroup['Hostgroup']['description']
        ];

        if (empty($hoststatusByUuids)) {
            return [
                'icon' => $this->errorIcon,
                'color' => 'text-primary',
                'background' => 'bg-color-blueLight',
                'Hostgroup' => $hostgroupLight
            ];
        }
        $worstHostState = array_values(
            Hash::sort($hoststatusByUuids, '{s}.Hoststatus.current_state', 'desc')
        );

        $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($worstHostState[0]['Hoststatus']);

        $icon = $this->hostIcons[$hoststatus->currentState()];
        $color = $hoststatus->HostStatusColor();
        $background = $hoststatus->HostStatusBackgroundColor();


        if ($hoststatus->isAcknowledged()) {
            $icon = $this->ackIcon;
        }

        if ($hoststatus->isInDowntime()) {
            $icon = $this->downtimeIcon;
        }

        if ($hoststatus->isAcknowledged() && $hoststatus->isInDowntime()) {
            $icon = $this->ackAndDowntimeIcon;
        }

        if ($hoststatus->currentState() > 0) {
            return [
                'icon' => $icon,
                'color' => $color,
                'background' => $background,
                'Hostgroup' => $hostgroupLight
            ];
        }

        //Check services for cumulated state (only if host is up)
        $hostIds = Hash::extract($hostgroup['Host'], '{n}.id');

        //Check services for cumulated state (only if host is up)
        $services = $Service->find('list', [
            'recursive' => -1,
            'fields' => [
                'Service.uuid'
            ],
            'conditions' => [
                'Service.host_id' => $hostIds,
                'Service.disabled' => 0
            ]
        ]);

        $ServicestatusFieds = new ServicestatusFields($this->DbBackend);
        $ServicestatusFieds->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
        $ServicestatusConditions = new ServicestatusConditions($this->DbBackend);
        $ServicestatusConditions->servicesWarningCriticalAndUnknown();
        $servicestatus = $Servicestatus->byUuid($services, $ServicestatusFieds, $ServicestatusConditions);

        if (!empty($servicestatus)) {
            $worstServiceState = array_values(
                Hash::sort($servicestatus, '{s}.Servicestatus.current_state', 'desc')
            );

            $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($worstServiceState[0]['Servicestatus']);
            $serviceIcon = $this->serviceIcons[$servicestatus->currentState()];

            if ($servicestatus->isAcknowledged()) {
                $serviceIcon = $this->ackIcon;
            }

            if ($servicestatus->isInDowntime()) {
                $serviceIcon = $this->downtimeIcon;
            }

            if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
                $serviceIcon = $this->ackAndDowntimeIcon;
            }
            return [
                'icon' => $serviceIcon,
                'color' => $servicestatus->ServiceStatusColor(),
                'background' => $servicestatus->ServiceStatusBackgroundColor(),
                'Hostgroup' => $hostgroupLight
            ];
        }

        return [
            'icon' => $icon,
            'color' => $color,
            'background' => $background,
            'Hostgroup' => $hostgroupLight
        ];
    }

    /**
     * @param Model $Service
     * @param Model $Servicestatus
     * @param $hostgroup
     * @return array
     */
    public function getServicegroupInformation(Model $Service, Model $Servicestatus, $servicegroup) {
        $ServicestatusFields = new ServicestatusFields($this->DbBackend);
        $ServicestatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();

        $serviceUuids = Hash::extract($servicegroup['Service'], '{n}.uuid');

        $servicestatusByUuids = $Servicestatus->byUuid($serviceUuids, $ServicestatusFields);

        $servicegroupLight = [
            'id' => (int)$servicegroup['Servicegroup']['id'],
            'name' => $servicegroup['Container']['name'],
            'description' => $servicegroup['Servicegroup']['description']
        ];

        if (empty($servicestatusByUuids)) {
            return [
                'icon' => $this->errorIcon,
                'color' => 'text-primary',
                'background' => 'bg-color-blueLight',
                'Servicegroup' => $servicegroupLight
            ];
        }
        $worstServiceState = array_values(
            Hash::sort($servicestatusByUuids, '{s}.Servicestatus.current_state', 'desc')
        );

        $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($worstServiceState[0]['Servicestatus']);

        $icon = $this->serviceIcons[$servicestatus->currentState()];
        $color = $servicestatus->ServiceStatusColor();
        $background = $servicestatus->ServiceStatusBackgroundColor();


        if ($servicestatus->isAcknowledged()) {
            $icon = $this->ackIcon;
        }

        if ($servicestatus->isInDowntime()) {
            $icon = $this->downtimeIcon;
        }

        if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
            $icon = $this->ackAndDowntimeIcon;
        }

        return [
            'icon' => $icon,
            'color' => $color,
            'background' => $background,
            'Servicegroup' => $servicegroupLight
        ];
    }

    /**
     * @param Model $Host
     * @param Model $Hoststatus
     * @param Model $Service
     * @param Model $Servicestatus
     * @param Model $Map
     * @param $map
     * @return array
     */
    public function getMapInformation(Model $Hoststatus, Model $Servicestatus, $map, $hosts, $services) {
        if (empty($hosts) && empty($services)) {
            return [
                'icon' => $this->errorIcon,
                'color' => 'text-primary',
                'background' => 'bg-color-blueLight',
                'Map' => $map
            ];
        }
        $map = [
            'id' => $map['Map']['id'],
            'name' => $map['Map']['name'],
            'title' => $map['Map']['title']
        ];
        $hostsUuids = Hash::extract($hosts, '{n}.Host.uuid');

        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
        $hoststatusByUuids = $Hoststatus->byUuid($hostsUuids, $HoststatusFields);
        if (empty($hoststatusByUuids)) {
            $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus([]);
            $icon = $this->errorIcon;
            $color = $hoststatus->HostStatusColor();
            $background = $hoststatus->HostStatusBackgroundColor();
            $iconProperty = $icon;
        } else {
            $worstHostState = array_values(
                Hash::sort($hoststatusByUuids, '{s}.Hoststatus.current_state', 'desc')
            );
            if (!empty($worstHostState)) {
                $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($worstHostState[0]['Hoststatus']);
            }
            $icon = $this->hostIcons[$hoststatus->currentState()];
            $color = $hoststatus->HostStatusColor();
            $background = $hoststatus->HostStatusBackgroundColor();
            $iconProperty = $icon;


            if ($hoststatus->isAcknowledged()) {
                $iconProperty = $this->ackIcon;
            }

            if ($hoststatus->isInDowntime()) {
                $iconProperty = $this->downtimeIcon;
            }

            if ($hoststatus->isAcknowledged() && $hoststatus->isInDowntime()) {
                $iconProperty = $this->ackAndDowntimeIcon;
            }
            if ($hoststatus->currentState() > 0) {
                return [
                    'icon' => $icon,
                    'icon_property' => $iconProperty,
                    'color' => $color,
                    'background' => $background,
                    'Map' => $map
                ];
            }
        }

        $servicesUuids = Hash::extract($services, '{n}.Service.uuid');
        $ServicestatusFieds = new ServicestatusFields($this->DbBackend);
        $ServicestatusFieds->currentState()->scheduledDowntimeDepth()->problemHasBeenAcknowledged();
        $ServicestatusConditions = new ServicestatusConditions($this->DbBackend);
        $ServicestatusConditions->servicesWarningCriticalAndUnknown();
        $servicestatus = $Servicestatus->byUuid($servicesUuids, $ServicestatusFieds, $ServicestatusConditions);
        if (!empty($servicestatus)) {
            $worstServiceState = array_values(
                Hash::sort($servicestatus, '{s}.Servicestatus.current_state', 'desc')
            );
            $servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($worstServiceState[0]['Servicestatus']);
            $serviceIcon = $this->serviceIcons[$servicestatus->currentState()];

            $serviceIconProperty = $serviceIcon;
            if ($servicestatus->isAcknowledged()) {
                $serviceIconProperty = $this->ackIcon;
            }

            if ($servicestatus->isInDowntime()) {
                $serviceIconProperty = $this->downtimeIcon;
            }

            if ($servicestatus->isAcknowledged() && $servicestatus->isInDowntime()) {
                $serviceIconProperty = $this->ackAndDowntimeIcon;
            }

            return [
                'icon' => $serviceIcon,
                'icon_property' => $serviceIconProperty,
                'isAcknowledged' => $servicestatus->isAcknowledged(),
                'isInDowntime' => $servicestatus->isInDowntime(),
                'color' => $servicestatus->ServiceStatusColor(),
                'background' => $servicestatus->ServiceStatusBackgroundColor(),
                'Map' => $map,
            ];
        }
        return [
            'icon' => $icon,
            'icon_property' => $iconProperty,
            'isAcknowledged' => $hoststatus->isAcknowledged(),
            'isInDowntime' => $hoststatus->isInDowntime(),
            'color' => $color,
            'background' => $background,
            'Map' => $map
        ];
    }

    /**
     * @param Model $Service
     * @param Model $Hoststatus
     * @param Model $Servicestatus
     * @param $host
     * @param UserTime $UserTime
     * @return array
     */
    public function getHostSummary(Model $Service, Model $Hoststatus, Model $Servicestatus, $host, UserTime $UserTime) {
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->output()
            ->perfdata()
            ->currentCheckAttempt()
            ->maxCheckAttempts()
            ->lastCheck()
            ->nextCheck()
            ->lastStateChange()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();
        $hoststatus = $Hoststatus->byUuid($host['Host']['uuid'], $HoststatusFields);
        if (empty($hoststatus)) {
            $hoststatus['Hoststatus'] = [];
        }

        $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($hoststatus['Hoststatus'], $UserTime);

        $services = $Service->find('all', [
            'recursive' => -1,
            'contain' => [
                'Servicetemplate' => [
                    'fields' => [
                        'Servicetemplate.name'
                    ]
                ]
            ],
            'fields' => [
                'Service.id',
                'Service.name',
                'Service.uuid'
            ],
            'conditions' => [
                'Service.host_id' => $host['Host']['id'],
                'Service.disabled' => 0
            ]
        ]);

        $ServicestatusFieds = new ServicestatusFields($this->DbBackend);
        $ServicestatusFieds
            ->currentState()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged()
            ->output();
        $ServicestatusConditions = new ServicestatusConditions($this->DbBackend);

        $servicesUuids = Hash::extract($services, '{n}.Service.uuid');
        $servicestatusResults = $Servicestatus->byUuid($servicesUuids, $ServicestatusFieds, $ServicestatusConditions);
        $ServiceSummary = $Service->getServiceStateSummary($servicestatusResults, false);
        $serviceIdsGroupByState = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];
        $servicesResult = [];
        foreach ($services as $service) {
            $Service = new \itnovum\openITCOCKPIT\Core\Views\Service($service);
            if (isset($servicestatusResults[$Service->getUuid()])) {
                $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus(
                    $servicestatusResults[$Service->getUuid()]['Servicestatus']
                );
                $serviceIdsGroupByState[$Servicestatus->currentState()][] = $service['Service']['id'];
            } else {
                $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus(
                    ['Servicestatus' => []]
                );
            }

            $servicesResult[] = [
                'Service' => $Service->toArray(),
                'Servicestatus' => $Servicestatus->toArray()
            ];
        }
        $servicesResult = Hash::sort($servicesResult, '{s}.Servicestatus.currentState', 'desc');

        $Host = new \itnovum\openITCOCKPIT\Core\Views\Host($host);
        return [
            'Host' => $Host->toArray(),
            'Hoststatus' => $hoststatus->toArray(),
            'Services' => $servicesResult,
            'ServiceSummary' => $ServiceSummary,
            'ServiceIdsGroupByState' => $serviceIdsGroupByState
        ];
    }

    /**
     * @param Model $Service
     * @param Model $Hoststatus
     * @param Model $Servicestatus
     * @param $service
     * @param UserTime $UserTime
     * @return array
     */
    public function getServiceSummary(Model $Service, Model $Hoststatus, Model $Servicestatus, $service, UserTime $UserTime) {
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();
        $hoststatus = $Hoststatus->byUuid($service['Host']['uuid'], $HoststatusFields);
        if (empty($hoststatus)) {
            $hoststatus['Hoststatus'] = [];
        }

        $hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus(
            $hoststatus['Hoststatus'],
            $UserTime
        );

        $ServicestatusFieds = new ServicestatusFields($this->DbBackend);
        $ServicestatusFieds
            ->currentState()
            ->isHardstate()
            ->output()
            ->perfdata()
            ->currentCheckAttempt()
            ->maxCheckAttempts()
            ->lastCheck()
            ->nextCheck()
            ->lastStateChange()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        $ServicestatusConditions = new ServicestatusConditions($this->DbBackend);

        $Servicestatus = $Servicestatus->byUuid($service['Service']['uuid'], $ServicestatusFieds, $ServicestatusConditions);
        $Service = new \itnovum\openITCOCKPIT\Core\Views\Service($service);
        if (!empty($Servicestatus)) {
            $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus(
                $Servicestatus['Servicestatus'],
                $UserTime
            );
        } else {
            $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus(
                ['Servicestatus' => []]
            );
        }
        $Host = new \itnovum\openITCOCKPIT\Core\Views\Host($service);

        return [
            'Host' => $Host->toArray(),
            'Hoststatus' => $hoststatus->toArray(),
            'Service' => $Service->toArray(),
            'Servicestatus' => $Servicestatus->toArray()
        ];
    }

    /**
     * @param Model $Service
     * @param Model $Hoststatus
     * @param Model $Servicestatus
     * @param $hostgroup
     * @param UserTime $UserTime
     * @return array
     */
    public function getHostgroupSummary(Model $Host, Model $Service, Model $Hoststatus, Model $Servicestatus, $hostgroup) {
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->output()
            ->perfdata()
            ->currentCheckAttempt()
            ->maxCheckAttempts()
            ->lastCheck()
            ->nextCheck()
            ->lastStateChange()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        $hostUuids = Hash::extract($hostgroup['Host'], '{n}.uuid');

        $hoststatusByUuids = $Hoststatus->byUuid($hostUuids, $HoststatusFields);
        $hostStateSummary = $Host->getHostStateSummary($hoststatusByUuids, false);

        $ServicestatusFieds = new ServicestatusFields($this->DbBackend);
        $ServicestatusFieds
            ->currentState()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged()
            ->output();
        $ServicestatusConditions = new ServicestatusConditions($this->DbBackend);


        if (empty($hoststatusByUuids)) {
            $hoststatusByUuids['Hoststatus'] = [];
        }
        $hoststatusResult = [];
        $cumulatedHostState = -1;
        $cumulatedServiceState = null;
        $allServiceStatus = [];
        $totalServiceStateSummary = [
            'state' => [
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0,
            ],
            'total' => 0
        ];

        $hostIdsGroupByState = [
            0 => [],
            1 => [],
            2 => []
        ];

        $serviceIdsGroupByState = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];


        foreach ($hostgroup['Host'] as $host) {
            $Host = new \itnovum\openITCOCKPIT\Core\Views\Host(['Host' => $host]);
            if (isset($hoststatusByUuids[$Host->getUuid()])) {
                $Hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus(
                    $hoststatusByUuids[$Host->getUuid()]['Hoststatus']
                );
                $hostIdsGroupByState[$Hoststatus->currentState()][] = $host['id'];

                if ($Hoststatus->currentState() > $cumulatedHostState) {
                    $cumulatedHostState = $Hoststatus->currentState();
                }
            } else {
                $Hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus(
                    ['Hoststatus' => []]
                );
            }
            $services = $Service->find('all', [
                'recursive' => -1,
                'contain' => [
                    'Servicetemplate' => [
                        'fields' => [
                            'Servicetemplate.name'
                        ]
                    ]
                ],
                'fields' => [
                    'Service.id',
                    'Service.name',
                    'Service.uuid'
                ],
                'conditions' => [
                    'Service.host_id' => $Host->getId(),
                    'Service.disabled' => 0
                ]
            ]);

            $servicesUuids = Hash::extract($services, '{n}.Service.uuid');
            $servicesIdsByUuid = Hash::combine($services, '{n}.Service.uuid', '{n}.Service.id');
            $servicestatusResults = $Servicestatus->byUuid($servicesUuids, $ServicestatusFieds, $ServicestatusConditions);

            $serviceIdsGroupByStatePerHost = [
                0 => [],
                1 => [],
                2 => [],
                3 => []
            ];
            foreach ($servicestatusResults as $serviceUuid =>  $servicestatusResult) {
                $allServiceStatus[] = $servicestatusResult['Servicestatus']['current_state'];
                $serviceIdsGroupByState[$servicestatusResult['Servicestatus']['current_state']][] = $servicesIdsByUuid[$serviceUuid];
                $serviceIdsGroupByStatePerHost[$servicestatusResult['Servicestatus']['current_state']][] = $servicesIdsByUuid[$serviceUuid];
            }

            $serviceStateSummary = $Service->getServiceStateSummary($servicestatusResults, false);
            $hoststatusResult[] = [
                'Host' => $Host->toArray(),
                'Hoststatus' => $Hoststatus->toArray(),
                'ServiceSummary' => $serviceStateSummary,
                'ServiceIdsGroupByState' => $serviceIdsGroupByStatePerHost
            ];

            foreach ($serviceStateSummary['state'] as $state => $stateValue) {
                $totalServiceStateSummary['state'][$state] += $stateValue;
            }
            $totalServiceStateSummary['total'] += $serviceStateSummary['total'];

        }
        $hoststatusResult = Hash::sort($hoststatusResult, '{s}.Hoststatus.currentState', 'desc');

        $hostgroup = [
            'id' => $hostgroup['Hostgroup']['id'],
            'name' => $hostgroup['Container']['name'],
            'description' => $hostgroup['Hostgroup']['description'],
            'HostSummary' => $hostStateSummary,
            'TotalServiceSummary' => $totalServiceStateSummary
        ];

        if ($cumulatedHostState > 0) {
            $CumulatedHostStatus = new \itnovum\openITCOCKPIT\Core\Hoststatus([
                'current_state' => $cumulatedHostState
            ]);
            $CumulatedHumanState = $CumulatedHostStatus->toArray()['humanState'];
        } else {
            if (!empty($allServiceStatus)) {
                $cumulatedServiceState = (int)max($allServiceStatus);
            }
            $CumulatedServiceStatus = new \itnovum\openITCOCKPIT\Core\Servicestatus([
                'current_state' => $cumulatedServiceState
            ]);
            $CumulatedHumanState = $CumulatedServiceStatus->toArray()['humanState'];
        }
        return [
            'Hostgroup' => $hostgroup,
            'Hosts' => $hoststatusResult,
            'CumulatedHumanState' => $CumulatedHumanState,
            'HostIdsGroupByState' => $hostIdsGroupByState,
            'ServiceIdsGroupByState' => $serviceIdsGroupByState
        ];
    }

    /**
     * @param Model $Service
     * @param Model $Servicestatus
     * @param $servicegroup
     * @return array
     */
    public function getServicegroupSummary(Model $Service, Model $Servicestatus, $servicegroup) {
        $ServicestatusFields = new ServicestatusFields($this->DbBackend);
        $ServicestatusFields
            ->currentState()
            ->isHardstate()
            ->output()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        $serviceUuids = Hash::extract($servicegroup['Service'], '{n}.uuid');
        $ServicestatusConditions = new ServicestatusConditions($this->DbBackend);

        $servicestatusResults = $Servicestatus->byUuid($serviceUuids, $ServicestatusFields, $ServicestatusConditions);
        $serviceStateSummary = $Service->getServiceStateSummary($servicestatusResults, false);
        $serviceIdsGroupByState = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];
        $cumulatedServiceState = null;
        $allServiceStatus = [];
        foreach ($servicegroup['Service'] as $service) {
            $Service = new \itnovum\openITCOCKPIT\Core\Views\Service([
                'Service' => $service,
                'Servicetemplate' => $service['Servicetemplate']
            ]);
            $Host = new \itnovum\openITCOCKPIT\Core\Views\Host(['Host' => $service['Host']]);

            if (isset($servicestatusResults[$Service->getUuid()])) {
                $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus(
                    $servicestatusResults[$Service->getUuid()]['Servicestatus']
                );
                $serviceIdsGroupByState[$Servicestatus->currentState()][] = $service['id'];

            } else {
                $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus(
                    ['Servicestatus' => []]
                );
            }
            $servicesResult[] = [
                'Service' => $Service->toArray(),
                'Servicestatus' => $Servicestatus->toArray(),
                'Host' => $Host->toArray()
            ];
        }
        $servicesResult = Hash::sort($servicesResult, '{s}.Servicestatus.currentState', 'desc');
        if (!empty($servicestatusResults)) {
            $cumulatedServiceState = Hash::apply($servicestatusResults, '{s}.Servicestatus.current_state', 'max');
        }
        $CumulatedServiceStatus = new \itnovum\openITCOCKPIT\Core\Servicestatus([
            'current_state' => $cumulatedServiceState
        ]);

        $servicegroup = [
            'id' => $servicegroup['Servicegroup']['id'],
            'name' => $servicegroup['Container']['name'],
            'description' => $servicegroup['Servicegroup']['description']
        ];

        return [
            'Servicegroup' => $servicegroup,
            'ServiceSummary' => $serviceStateSummary,
            'Services' => $servicesResult,
            'CumulatedHumanState' => $CumulatedServiceStatus->toArray()['humanState'],
            'ServiceIdsGroupByState' => $serviceIdsGroupByState
        ];
    }

    /**
     * @param Model $Host
     * @param Model $Hoststatus
     * @param Model $Service
     * @param Model $Servicestatus
     * @param $map
     * @param $hosts
     * @param $services
     * @param UserTime $UserTime
     * @return array map summary with host and service status overview
     */
    public function getMapSummary(Model $Host, Model $Hoststatus, Model $Service, Model $Servicestatus, $map, $hosts, $services, UserTime $UserTime) {
        $cumulatedHostState = null;
        $cumulatedServiceState = null;
        $notOkHosts = [];
        $notOkServices = [];
        $hostIdsGroupByState = [
            0 => [],
            1 => [],
            2 => []
        ];

        $serviceIdsGroupByState = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];
        $counterForNotOkHostAndService = 0;
        $limitForNotOkHostAndService = 20;

        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->lastCheck()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged()
            ->output();

        $hostUuids = Hash::extract($hosts, '{n}.Host.uuid');

        $hoststatusByUuids = $Hoststatus->byUuid($hostUuids, $HoststatusFields);
        $hostStateSummary = $Host->getHostStateSummary($hoststatusByUuids, false);

        $ServicestatusFieds = new ServicestatusFields($this->DbBackend);
        $ServicestatusFieds
            ->currentState()
            ->lastCheck()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged()
            ->output();
        $ServicestatusConditions = new ServicestatusConditions($this->DbBackend);

        $servicesUuids = Hash::extract($services, '{n}.Service.uuid');

        $servicestatusResults = $Servicestatus->byUuid($servicesUuids, $ServicestatusFieds, $ServicestatusConditions);
        $serviceStateSummary = $Service->getServiceStateSummary($servicestatusResults, false);

        if (!empty($hoststatusByUuids)) {
            $worstHostState = array_values(
                $hoststatusByUuids = Hash::sort($hoststatusByUuids, '{s}.Hoststatus.current_state', 'desc')
            );
            $cumulatedHostState = (int)$worstHostState[0]['Hoststatus']['current_state'];
            if ($cumulatedHostState > 0) {
                $hosts = Hash::combine($hosts, '{n}.Host.uuid', '{n}');
                foreach ($hoststatusByUuids as $hostUuid => $hoststatusByUuid) {
                    $hostStatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($hoststatusByUuid['Hoststatus'], $UserTime);
                    $currentHostState = $hostStatus->currentState();

                    $hostIdsGroupByState[$currentHostState][] = $hosts[$hostUuid]['Host']['id'];
                    $host = new \itnovum\openITCOCKPIT\Core\Views\Host($hosts[$hostUuid]);
                    if ($counterForNotOkHostAndService <= $limitForNotOkHostAndService && $currentHostState > 0) {
                        $notOkHosts[] = [
                            'Hoststatus' => $hostStatus->toArray(),
                            'Host' => $host->toArray()
                        ];
                        $counterForNotOkHostAndService++;
                    }
                }
            }
        }

        if (!empty($servicestatusResults)) {
            $worstServiceState = array_values(
                $servicestatusResults = Hash::sort($servicestatusResults, '{s}.Servicestatus.current_state', 'desc')
            );
            $cumulatedServiceState = (int)$worstServiceState[0]['Servicestatus']['current_state'];
            if ($cumulatedServiceState > 0) {
                $services = Hash::combine($services, '{n}.Service.uuid', '{n}');
                foreach ($servicestatusResults as $serviceUuid => $servicestatusByUuid) {
                    $serviceStatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($servicestatusByUuid['Servicestatus'], $UserTime);
                    $currentServiceState = $serviceStatus->currentState();
                    $serviceIdsGroupByState[$currentServiceState][] = $services[$serviceUuid]['Service']['id'];

                    $service = new \itnovum\openITCOCKPIT\Core\Views\Service($services[$serviceUuid]);
                    if ($counterForNotOkHostAndService <= $limitForNotOkHostAndService && $currentServiceState > 0) {
                        $notOkServices[] = [
                            'Servicestatus' => $serviceStatus->toArray(),
                            'Service' => $service->toArray()
                        ];
                        $counterForNotOkHostAndService++;
                    }
                }
            }
        }
        $CumulatedHostStatus = new \itnovum\openITCOCKPIT\Core\Hoststatus([
            'current_state' => $cumulatedHostState
        ]);

        $CumulatedHumanState = $CumulatedHostStatus->toArray()['humanState'];
        if (($cumulatedHostState === 0 || is_null($cumulatedHostState)) && !is_null($cumulatedServiceState)) {
            $CumulatedServiceStatus = new \itnovum\openITCOCKPIT\Core\Servicestatus([
                'current_state' => $cumulatedServiceState
            ]);
            $CumulatedHumanState = $CumulatedServiceStatus->toArray()['humanState'];
        }

        $map = [
            'id' => $map['Map']['id'],
            'name' => $map['Map']['name'],
            'title' => $map['Map']['title'],
            'object_id' => $map['Mapitem']['object_id']
        ];

        return [
            'Map' => $map,
            'HostSummary' => $hostStateSummary,
            'ServiceSummary' => $serviceStateSummary,
            'CumulatedHumanState' => $CumulatedHumanState,
            'NotOkHosts' => $notOkHosts,
            'NotOkServices' => $notOkServices,
            'HostIdsGroupByState' => $hostIdsGroupByState,
            'ServiceIdsGroupByState' => $serviceIdsGroupByState
        ];
    }

    /**
     * @param Model $Map
     * @param $dependentMapsIds
     * @param Model $Hostgroup
     * @param Model $Servicegroup
     * @return array with host and service ids
     */
    public function getAllDependentMapsElements(Model $Map, $dependentMapsIds, Model $Hostgroup, Model $Servicegroup) {
        $allDependentMapElements = $Map->find('all', [
            'recursive' => -1,
            'contain' => [
                'Mapitem' => [
                    'conditions' => [
                        'NOT' => [
                            'Mapitem.type' => 'map'
                        ]
                    ],
                    'fields' => [
                        'Mapitem.type',
                        'Mapitem.object_id'
                    ]
                ],
                'Mapline' => [
                    'conditions' => [
                        'NOT' => [
                            'Mapline.type' => 'stateless'
                        ]
                    ],
                    'fields' => [
                        'Mapline.type',
                        'Mapline.object_id'
                    ]
                ],
                'Mapgadget' => [
                    'fields' => [
                        'Mapgadget.type',
                        'Mapgadget.object_id'
                    ]
                ]
            ],
            'conditions' => [
                'Map.id' => $dependentMapsIds
            ]
        ]);
        $mapElementsByCategory = [
            'host' => [],
            'hostgroup' => [],
            'service' => [],
            'servicegroup' => []
        ];
        $allDependentMapElements = Hash::filter($allDependentMapElements);
        foreach ($allDependentMapElements as $allDependentMapElementArray) {
            foreach ($allDependentMapElementArray as $mapElementKey => $mapElementData) {
                if ($mapElementKey === 'Map') {
                    continue;
                }
                foreach ($mapElementData as $mapElement) {
                    $mapElementsByCategory[$mapElement['type']][$mapElement['object_id']] = $mapElement['object_id'];
                }
            }

        }
        $hostIds = $mapElementsByCategory['host'];
        if (!empty($mapElementsByCategory['hostgroup'])) {
            $query = [
                'recursive' => -1,
                'joins' => [
                    [
                        'table' => 'hosts_to_hostgroups',
                        'type' => 'INNER',
                        'alias' => 'HostsToHostgroups',
                        'conditions' => 'HostsToHostgroups.hostgroup_id = Hostgroup.id',
                    ],
                ],
                'fields' => [
                    'HostsToHostgroups.host_id'

                ],
                'conditions' => [
                    'Hostgroup.id' => $mapElementsByCategory['hostgroup']
                ]
            ];
            if ($this->hasRootPrivileges === false) {
                $query['conditions']['Hostgroup.container_id'] = $this->MY_RIGHTS;
            }

            $hostIdsByHostgroup = $Hostgroup->find('all', $query);
            foreach ($hostIdsByHostgroup as $hostIdByHostgroup) {
                $hostIds[$hostIdByHostgroup['HostsToHostgroups']['host_id']] = $hostIdByHostgroup['HostsToHostgroups']['host_id'];
            }
        }
        $serviceIds = $mapElementsByCategory['service'];
        if (!empty($mapElementsByCategory['servicegroup'])) {
            $query = [
                'recursive' => -1,
                'joins' => [
                    [
                        'table' => 'services_to_servicegroups',
                        'type' => 'INNER',
                        'alias' => 'ServicesToServicegroups',
                        'conditions' => 'ServicesToServicegroups.servicegroup_id = Servicegroup.id',
                    ],
                ],
                'fields' => [
                    'ServicesToServicegroups.service_id'

                ],
                'conditions' => [
                    'Servicegroup.id' => $mapElementsByCategory['servicegroup']
                ]
            ];
            if ($this->hasRootPrivileges === false) {
                $query['conditions']['Servicegroup.container_id'] = $this->MY_RIGHTS;
            }

            $serviceIdsByServicegroup = $Servicegroup->find('all', $query);
            foreach ($serviceIdsByServicegroup as $serviceIdByServicegroup) {
                $serviceIds[$serviceIdByServicegroup['ServicesToServicegroups']['service_id']] = $serviceIdByServicegroup['ServicesToServicegroups']['service_id'];
            }
        }
        return [
            'hostIds' => $hostIds,
            'serviceIds' => $serviceIds
        ];
    }
}

