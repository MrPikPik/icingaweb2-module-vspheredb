<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use dipl\Html\Html;
use dipl\Html\Link;
use dipl\Translation\TranslationHelper;
use dipl\Web\Widget\NameValueTable;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Widget\SpectreMelddownBiosInfo;

class HostInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var HostSystem */
    protected $host;

    /** @var PathLookup */
    protected $pathLookup;

    public function __construct(HostSystem $host, PathLookup $loopup)
    {
        $this->host = $host;
        $this->pathLookup = $loopup;
    }

    protected function getDb()
    {
        return $this->host->getConnection();
    }

    protected function assemble()
    {
        $host = $this->host;
        $uuid = $host->get('uuid');
        $lookup = $this->pathLookup;

        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($uuid, false)) as $parentUuid => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/hosts',
                ['uuid' => bin2hex($parentUuid)],
                ['data-base-target' => '_main']
            ));
        }

        $this->addNameValuePairs([
            $this->translate('UUID')         => $host->get('sysinfo_uuid'),
            $this->translate('API Version')  => $host->get('product_api_version'),
            $this->translate('Product Name') => $host->get('product_full_name'),
            $this->translate('Memory')       => $this->getFormattedMemory(),
            $this->translate('Path')         => $path,
            $this->translate('Power')        => $host->get('runtime_power_state'),
            $this->translate('BIOS Version') => new SpectreMelddownBiosInfo($host),
            // $this->translate('BIOS Release Date') => $vm->get('bios_release_date'),
            $this->translate('Vendor')       => $host->get('sysinfo_vendor'),
            $this->translate('Model')        => $host->get('sysinfo_model'),
            $this->translate('Service Tag')  => $this->getFormattedServiceTag(),
            $this->translate('CPU Model')    => $host->get('hardware_cpu_model'),
            $this->translate('CPU Packages') => $host->get('hardware_cpu_packages'),
            $this->translate('CPU Cores')    => $host->get('hardware_cpu_cores'),
            $this->translate('CPU Threads')  => $host->get('hardware_cpu_threads'),
            $this->translate('HBAs')         => $host->get('hardware_num_hba'),
            $this->translate('NICs')         => $host->get('hardware_num_nic'),
            $this->translate('Vms')          => Link::create(
                $host->countVms(),
                'vspheredb/host/vms',
                ['uuid' => bin2hex($uuid)]
            ),
        ]);
    }

    protected function getFormattedServiceTag()
    {
        $host = $this->host;
        $tag = $host->get('service_tag');
        if ($host->get('sysinfo_vendor') === 'Dell Inc.') {
            $url = sprintf(
                'http://www.dell.com/support/home/de/de/debsdt1/product-support/servicetag/%s/drivers',
                strtolower($tag)
            );

            return Html::tag(
                'a',
                [
                    'href'   => $url,
                    'target' => '_blank',
                    'title'  => $this->translate('Dell Support Page')
                ],
                $tag
            );
        } else {
            return $tag;
        }
    }

    protected function getFormattedMemory()
    {
        return number_format(
            $this->host->get('hardware_memory_size_mb'),
            0,
            ',',
            '.'
        ) . ' MB';
    }
}
