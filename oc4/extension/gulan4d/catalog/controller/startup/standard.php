<?php

namespace Opencart\Catalog\Controller\Extension\Gulan4d\Startup;

class Standard extends \Opencart\System\Engine\Controller
{
    /**
     *
     */
    private const ALL_PRODUCTS_URL = 'product/allproducts';


    /**
     * @return void
     */
    public function index(): void
    {
        if ($this->config->get('theme_standard_status')) {
            $this->event->register('view/*/before',
                new \Opencart\System\Engine\Action('extension/gulan4d/startup/standard.event'));
            $this->event->register('view/*/before',
                new \Opencart\System\Engine\Action('extension/gulan4d/startup/standard.allproducts'));
        }
    }


    /**
     * @param  string  $route
     * @param  array  $args
     * @param  mixed  $output
     *
     * @return void
     */
    public function event(string &$route, array &$args, mixed &$output): void
    {
        $override = [
            'common/header',
            'common/footer',
        ];

        if (in_array($route, $override)) {
            $route = 'extension/gulan4d/'.$route;
        }
    }


    /**
     * @param  string  $route
     * @param  array  $args
     * @param  mixed  $output
     *
     * @return void
     */
    public function allproducts(string &$route, array &$args, mixed &$output): void
    {
        $override = [
            'common/menu',
        ];

        $this->load->language('extension/gulan4d/common/menu');

        $args['allproducts_url'] = $this->url->link(self::ALL_PRODUCTS_URL);

        if (in_array($route, $override)) {
            $route = 'extension/gulan4d/'.$route;
        }
    }
}
