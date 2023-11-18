<?php

namespace Opencart\Catalog\Controller\Extension\Allproducts\Module;

use Opencart\System\Engine\Registry;

class Allproducts extends \Opencart\System\Engine\Controller
{

    private const BASE_PATH = 'extension/allproducts/module/allproducts.index';

    /* Views */
    private const PAGE_VIEW = 'extension/allproducts/module/allproducts';


    /* Translations */
    private const TRANSLATION = 'extension/allproducts/module/allproducts';


    /**
     * ?route=extension/allproducts/module/allproducts.index
     * @return null
     */
    public function index()
    {
        $this->load->language(self::TRANSLATION);
        $this->load->language('product/category');

        $this->load->model('catalog/product');

        $this->load->model('tool/image');


        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'p.sort_order';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int) $this->request->get['page'];
        } else {
            $page = 1;
        }

        if (isset($this->request->get['limit']) && (int) $this->request->get['limit']) {
            $limit = (int) $this->request->get['limit'];
        } else {
            $limit = $this->config->get('config_pagination');
        }


        $this->document->setTitle($this->language->get('text_allproducts'));
        $this->document->setDescription($this->language->get('text_allproducts_description'));
        $this->document->setKeywords($this->language->get('text_allproducts_keyword'));


        $data['breadcrumbs'] = array();


        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'language='.$this->config->get('config_language')),
            'separator' => false,
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_allproducts'),
            'href'      => $this->url->link(self::BASE_PATH),
            'separator' => ' :: ',
        );

        $data['heading_title'] = $this->language->get('text_allproducts');

        $data['text_empty']        = $this->language->get('text_empty');
        $data['text_quantity']     = $this->language->get('text_quantity');
        $data['text_manufacturer'] = $this->language->get('text_manufacturer');
        $data['text_model']        = $this->language->get('text_model');
        $data['text_price']        = $this->language->get('text_price');
        $data['text_tax']          = $this->language->get('text_tax');
        $data['text_points']       = $this->language->get('text_points');
        $data['text_compare']      = sprintf($this->language->get('text_compare'),
            (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));
        $data['text_display']      = $this->language->get('text_display');
        $data['text_list']         = $this->language->get('text_list');
        $data['text_grid']         = $this->language->get('text_grid');
        $data['text_sort']         = $this->language->get('text_sort');
        $data['text_limit']        = $this->language->get('text_limit');
        $data['text_error']        = $data['text_empty'];

        $data['button_cart']     = $this->language->get('button_cart');
        $data['button_wishlist'] = $this->language->get('button_wishlist');
        $data['button_compare']  = $this->language->get('button_compare');
        $data['button_continue'] = $this->language->get('button_continue');

        $data['compare'] = $this->url->link('product/compare');

        $url = '';


        if (isset($this->request->get['sort'])) {
            $url .= '&sort='.$this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order='.$this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page='.$this->request->get['page'];
        }

        if (isset($this->request->get['limit'])) {
            $url .= '&limit='.$this->request->get['limit'];
        }


        $this->load->model('catalog/product');

        $data['products'] = [];

        $querydata = [
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $limit,
            'limit' => $limit,
        ];

        $product_total = $this->model_catalog_product->getTotalProducts($querydata);

        $results = $this->model_catalog_product->getProducts($querydata);


        foreach ($results as $result) {
            if (is_file(DIR_IMAGE.html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
                $image = $this->model_tool_image->resize(html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'),
                    $this->config->get('config_image_product_width'),
                    $this->config->get('config_image_product_height'));
            } else {
                $image = $this->model_tool_image->resize('placeholder.png',
                    $this->config->get('config_image_product_width'),
                    $this->config->get('config_image_product_height'));
            }

            if ($this->customer->isLogged() || ! $this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'],
                    $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            if ((float) $result['special']) {
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'],
                    $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }

            if ($this->config->get('config_tax')) {
                $tax = $this->currency->format((float) $result['special'] ? $result['special'] : $result['price'],
                    $this->session->data['currency']);
            } else {
                $tax = false;
            }

            $product_data = [
                'product_id'  => $result['product_id'],
                'thumb'       => $image,
                'name'        => $result['name'],
                'description' => oc_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES,
                        'UTF-8'))), 0, $this->config->get('config_product_description_length')).'..',
                'price'       => $price,
                'special'     => $special,
                'tax'         => $tax,
                'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
                'rating'      => $result['rating'],
                'href'        => $this->url->link('product/product',
                    'language='.$this->config->get('config_language').'&product_id='.$result['product_id'].$url),
            ];

            $data['products'][] = $this->load->controller('product/thumb', $product_data);
        }


        $url = '';

        if (isset($this->request->get['limit'])) {
            $url .= '&limit='.$this->request->get['limit'];
        }

        $data['sorts'] = [];

        $data['sorts'][] = [
            'text'  => $this->language->get('text_default'),
            'value' => 'p.sort_order-ASC',
            'href'  => $this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&sort=p.sort_order&order=ASC'.$url),
        ];

        $data['sorts'][] = [
            'text'  => $this->language->get('text_name_asc'),
            'value' => 'pd.name-ASC',
            'href'  => $this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&sort=pd.name&order=ASC'.$url),
        ];

        $data['sorts'][] = [
            'text'  => $this->language->get('text_name_desc'),
            'value' => 'pd.name-DESC',
            'href'  => $this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&sort=pd.name&order=DESC'.$url),
        ];

        $data['sorts'][] = [
            'text'  => $this->language->get('text_price_asc'),
            'value' => 'p.price-ASC',
            'href'  => $this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&sort=p.price&order=ASC'.$url),
        ];

        $data['sorts'][] = [
            'text'  => $this->language->get('text_price_desc'),
            'value' => 'p.price-DESC',
            'href'  => $this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&sort=p.price&order=DESC'.$url),
        ];

        if ($this->config->get('config_review_status')) {
            $data['sorts'][] = [
                'text'  => $this->language->get('text_rating_desc'),
                'value' => 'rating-DESC',
                'href'  => $this->url->link(self::BASE_PATH,
                    'language='.$this->config->get('config_language').'&sort=rating&order=DESC'.$url),
            ];

            $data['sorts'][] = [
                'text'  => $this->language->get('text_rating_asc'),
                'value' => 'rating-ASC',
                'href'  => $this->url->link(self::BASE_PATH,
                    'language='.$this->config->get('config_language').'&sort=rating&order=ASC'.$url),
            ];
        }

        $data['sorts'][] = [
            'text'  => $this->language->get('text_model_asc'),
            'value' => 'p.model-ASC',
            'href'  => $this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&sort=p.model&order=ASC'.$url),
        ];

        $data['sorts'][] = [
            'text'  => $this->language->get('text_model_desc'),
            'value' => 'p.model-DESC',
            'href'  => $this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&sort=p.model&order=DESC'.$url),
        ];

        $url = '';


        if (isset($this->request->get['sort'])) {
            $url .= '&sort='.$this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order='.$this->request->get['order'];
        }


        $data['limits'] = [];

        $limits = array_unique([$this->config->get('config_pagination'), 25, 50, 75, 100]);

        sort($limits);

        foreach ($limits as $value) {
            $data['limits'][] = [
                'text'  => $value,
                'value' => $value,
                'href'  => $this->url->link(self::BASE_PATH,
                    'language='.$this->config->get('config_language').$url.'&limit='.$value),
            ];
        }

        $url = '';


        if (isset($this->request->get['sort'])) {
            $url .= '&sort='.$this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order='.$this->request->get['order'];
        }

        if (isset($this->request->get['limit'])) {
            $url .= '&limit='.$this->request->get['limit'];
        }


        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $product_total,
            'page'  => $page,
            'limit' => $limit,
            'url'   => $this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').$url.'&page={page}'),
        ]);

        $data['results'] = sprintf($this->language->get('text_pagination'),
            ($product_total) ? (($page - 1) * $limit) + 1 : 0,
            ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit),
            $product_total, ceil($product_total / $limit));

        // http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
        if ($page == 1) {
            $this->document->addLink($this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language')), 'canonical');
        } else {
            $this->document->addLink($this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&page='.$page), 'canonical');
        }

        if ($page > 1) {
            $this->document->addLink($this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').(($page - 2) ? '&page='.($page - 1) : '')), 'prev');
        }

        if ($limit && ceil($product_total / $limit) > $page) {
            $this->document->addLink($this->url->link(self::BASE_PATH,
                'language='.$this->config->get('config_language').'&page='.($page + 1)), 'next');
        }


        $data['sort']  = $sort;
        $data['order'] = $order;
        $data['limit'] = $limit;

        $data['continue'] = $this->url->link('common/home', 'language='.$this->config->get('config_language'));

        $data['column_left']    = $this->load->controller('common/column_left');
        $data['column_right']   = $this->load->controller('common/column_right');
        $data['content_top']    = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer']         = $this->load->controller('common/footer');
        $data['header']         = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view(self::PAGE_VIEW, $data));

        return null;
    }
}
