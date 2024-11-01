<?php
//security check
if (!defined('ABSPATH')) {
    exit;
}

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

class Spottr
{
    public function init()
    {
        // Add the admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        //style
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        //ajax spottr_login
        add_action('wp_ajax_spottr_login', array($this, 'spottr_login'));
        add_action('wp_ajax_nopriv_spottr_login', array($this, 'spottr_login'));
        //ajax spottr_disconnect
        add_action('wp_ajax_spottr_disconnect', array($this, 'spottr_disconnect'));
        add_action('wp_ajax_nopriv_spottr_disconnect', array($this, 'spottr_disconnect'));
        //ajax spottr content
        add_action('wp_ajax_spottr_content', array($this, 'spottr_content'));
        add_action('wp_ajax_nopriv_spottr_content', array($this, 'spottr_content'));
        //edit product table columns
        add_filter('manage_edit-product_cat_columns', array($this, 'product_cat_columns'), 11);
        //add product_cat column content
        add_filter('manage_product_cat_custom_column', array($this, 'product_cat_column_content'), 999, 3);
        //add product_tag column content
        add_filter('manage_product_tag_custom_column', array($this, 'product_tag_column_content'), 999, 3);
        //add product_cat column content
        add_filter('manage_edit-product_tag_columns', array($this, 'product_tag_columns'), 11);
        //edit product cat page
        add_action('product_cat_edit_form_fields', array($this, 'product_cat_edit_form_fields'), 10, 2);
        //edit product tag page
        add_action('product_tag_edit_form_fields', array($this, 'product_tag_edit_form_fields'), 10, 2);
        //product table column
        add_filter('manage_edit-product_columns', array($this, 'product_columns'), 11);
        //add product column content
        add_filter('manage_product_posts_custom_column', array($this, 'product_column_content'), 999, 3);
        //after product edit title
        add_action('edit_form_after_title', array($this, 'edit_form_after_title'));
        //save product
        add_action('save_post', array($this, 'save_post'));
        //ajax sync_spottr
        add_action('wp_ajax_sync_spottr', array($this, 'sync_spottr'));
        add_action('wp_ajax_nopriv_sync_spottr', array($this, 'sync_spottr'));
    }

    //edit_form_after_title
    public function edit_form_after_title($post)
    {
        if ($post->post_type == 'product') {
            $spottr_product = get_post_meta($post->ID, 'spottr_product', true);
            //check token get_option('spottr_token')
            $token = get_option('spottr_token');
            //check if $token
            if (!$token) {
                echo '<div class="spottr-product"><span class="dashicons dashicons-no" style="color: red;"></span> <a href="https://spottr.app" target="_blank">Spottr</a> is not connected to this site. <a href="' . esc_url(admin_url('admin.php?page=spottr')) . '" target="_blank">Connect</a></div>';
                return;
            }

            //check if woocommerce currency is NGN
            $currency = get_woocommerce_currency();
            if ($currency != 'NGN') {
                echo '<div class="spottr-product"><span class="dashicons dashicons-no" style="color: red;"></span> <a href="https://spottr.app" target="_blank">Spottr</a> only supports NGN currency. <a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=general')) . '" target="_blank">Change currency</a></div>';
                return;
            }

            if ($spottr_product) {
                echo '<div class="spottr-product"><span class="dashicons dashicons-yes" style="color: green;"></span> Synced with <a href="' . esc_url('https://spottr.app') . '" target="_blank">Spottr</a></div>';
            } else {
                echo '<div class="spottr-product"><input type="checkbox" name="spottr_product"/> Sync this product with <a href="' . esc_url('https://spottr.app') . '" target="_blank">Spottr</a></div>';
?>
                <input type="hidden" name="lat" id="">
                <input type="hidden" name="lng" id="">
        <?php
            }
        }
    }

    //save_post
    public function save_post($post_id)
    {
        if (isset($_POST['spottr_product'])) {
            $this->syncFunction($post_id);
        } else {
            // file_put_contents(__DIR__ . '/spottr.log', 'spottr_product not set');
        }
    }

    //getAddress
    public function getAddress($lat, $lng)
    {
        try {
            //get option
            $spottr_public_keys = get_option('spottr_public_keys', array());
            $gapi = $spottr_public_keys['spottr_gapi'];
            //check if lat and lng
            if ($lat && $lng) {
                $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng . '&sensor=true&key=' . $gapi;
                $response = wp_remote_get($url);
                $response = json_decode($response['body']);
                if ($response->status == 'OK') {
                    $address = $response->results[0]->formatted_address;
                    return $address;
                } else {
                    return "No address found";
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage()  . ' ' . $e->getLine() . ' ' . $e->getFile());
            return "Error getting address";
        }
    }

    //uploadToCloudinary
    public function uploadToCloudinary($images)
    {
        //if images is not set
        if (!$images) {
            return array();
        }
        try {
            //get option
            $spottr_public_keys = get_option('spottr_public_keys', array());
            //cloudinary_config
            $config = new Configuration();
            $config->cloud->cloudName = $spottr_public_keys['spottr_cloudname'];
            $config->cloud->apiKey = $spottr_public_keys['spottr_cloudapi'];
            $config->cloud->apiSecret = $spottr_public_keys['spottr_cloud_secret'];
            $config->url->secure = true;
            $cloudinary = new Cloudinary($config);
            //upload images
            $uploaded_images = array();
            foreach ($images as $image_id => $image) {
                //check if is ssl
                if (!is_ssl()) {
                    $image = "https://www.bhphotovideo.com/images/images500x500/acer_nx_as1aa_002_14_chromebook_514_arm_1669201824_1735702.jpg";
                }
                //get meta spottr_image
                $spottr_image = get_post_meta($image_id, 'spottr_image', true);
                //check if $spottr_image
                if ($spottr_image) {
                    $uploaded_images[] = $spottr_image;
                    continue;
                }
                $uploaded_image = $cloudinary->uploadApi()->upload($image, array("folder" => "spottr"));
                //update meta
                update_post_meta($image_id, 'spottr_image', $uploaded_image['secure_url']);
                $uploaded_images[] = $uploaded_image['secure_url'];
            }
            return $uploaded_images;
        } catch (Exception $e) {
            error_log($e->getMessage()  . ' ' . $e->getLine() . ' ' . $e->getFile());
            return array();
        }
    }

    //syncFunction
    public function syncFunction($post_id)
    {
        try {
            $product_title = get_the_title($post_id) ?: "Untitled";
            $product_description = get_post_field('post_content', $post_id);
            //remove html from description
            $product_description = strip_tags($product_description);
            //check if empty $product_description
            if (empty($product_description)) {
                $product_description = $product_title;
            }
            //get product categories
            $product_categories = wp_get_post_terms($post_id, 'product_cat');
            $product_category_ids = array();
            foreach ($product_categories as $product_category) {
                //get term meta spottr_term_id
                $spottr_term_id = get_term_meta($product_category->term_id, 'spottr_term_id', true);
                if ($spottr_term_id) {
                    $product_category_ids[] = $spottr_term_id;
                } else {
                    $product_category_ids[] = SPOTTR_DEFAULT_CATEGORY_ID;
                }
            }
            //get product tags
            $product_tags = wp_get_post_terms($post_id, 'product_tag');
            $product_tag_ids = array();
            foreach ($product_tags as $product_tag) {
                //get term meta spottr_term_id
                $spottr_term_id = get_term_meta($product_tag->term_id, 'spottr_term_id', true);
                if ($spottr_term_id) {
                    $product_tag_ids[] = $spottr_term_id;
                } else {
                    $product_tag_ids[] = SPOTTR_DEFAULT_TAG_ID;
                }
            }
            $currency = get_woocommerce_currency();
            $lat = sanitize_text_field($_POST['lat']);
            $lng = sanitize_text_field($_POST['lng']);
            $address = $this->getAddress($lat, $lng);
            $product = wc_get_product($post_id);
            $product_price = $product->get_regular_price();
            $product_images = $product->get_gallery_image_ids();
            $thumbnail_id = $product->get_image_id();
            //image url
            $product_images[] = $thumbnail_id;
            $product_image_urls = array();
            foreach ($product_images as $product_image) {
                $product_image_urls[$product_image] = wp_get_attachment_image_url($product_image, 'full');
            }
            //upload to cloudinary
            $cloudinary = $this->uploadToCloudinary($product_image_urls);
            //check if cloudinary is not empty
            if (empty($cloudinary)) {
                error_log("Cloudinary is empty");
                return;
            } else {
                $product_image_urls = $cloudinary;
            }
            //check if product image count is 3
            if (count($product_image_urls) < 3) {
                //duplicate images
                $duplicates = [];
                for ($i = 0; $i < 3; $i++) {
                    $duplicates[] = $product_image_urls[0];
                }
                $product_image_urls = $duplicates;
            }

            //check if product is published
            $product_status = get_post_status($post_id);
            if ($product_status == 'publish') {
                $product_status = $product_title == "Untitled" ? false : true;
            } else {
                $product_status = false;
            }

            $response = Requests::post(
                SPOTTR_API_URL . 'products',
                array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . get_option('spottr_token')
                ),
                json_encode(array(
                    'name' => $product_title,
                    'currency' => $currency,
                    'description' => $product_description,
                    'categoryIds' => $product_category_ids ?: array(SPOTTR_DEFAULT_CATEGORY_ID),
                    'lng' => floatval($lng),
                    'lat' => floatval($lat),
                    'address' => $address,
                    'tagIds' => $product_tag_ids ?: array(SPOTTR_DEFAULT_TAG_ID),
                    'amount' => floatval($product_price),
                    'images' => $product_image_urls,
                    'published' => $product_status
                ))
            );
            //check if response is 200
            if ($response->status_code == 201) {
                $response_body = json_decode($response->body);
                //update post meta spottr_product
                update_post_meta($post_id, 'spottr_product', $response_body->data->id);
                return [
                    "code" => 200,
                    "message" => "Product synced successfully",
                ];
            } else {
                //delete post meta spottr_product
                delete_post_meta($post_id, 'spottr_product');
                return [
                    "code" => 500,
                    "message" => $response->body
                ];
            }
        } catch (\Exception $e) {
            //delete post meta spottr_product
            delete_post_meta($post_id, 'spottr_product');
            //log error
            error_log($e->getMessage());
            return [
                "code" => 500,
                "message" => $e->getMessage()
            ];
        }
    }

    //sync_spottr
    public function sync_spottr()
    {
        $product_id = sanitize_text_field($_POST['product_id']);
        try {
            $res = $this->syncFunction($product_id);
            //check if code is 200
            if ($res['code'] == 200) {
                wp_send_json(
                    [
                        "code" => 200,
                        "message" => "Product synced successfully",
                    ]
                );
            } else {
                wp_send_json(
                    [
                        "code" => 500,
                        "message" => $res['message'],
                    ]
                );
            }
        } catch (\Exception $e) {
            wp_send_json(
                [
                    "code" => 500,
                    "message" => $e->getMessage(),
                ]
            );
        }
    }

    //product_columns
    public function product_columns($columns)
    {
        $columns['spottr_product'] = 'Spottr';
        //index 4
        $columns = array_slice($columns, 0, 4, true) +
            array('spottr_product' => 'Spottr') +
            array_slice($columns, 4, count($columns) - 1, true);
        return $columns;
    }

    //product_column_content
    public function product_column_content($column, $post_id)
    {
        if ($column == 'spottr_product') {
            $spottr_product = get_post_meta($post_id, 'spottr_product', true);
            if ($spottr_product) {
                echo '<span class="dashicons dashicons-yes" style="color: green;"></span> Synced';
            } else {
                echo '<span class="dashicons dashicons-no" style="color: red;"></span> <a href="#" class="spottr_product" onclick="syncSpottr(this, event)" data-id="' . $post_id . '">Sync</a>';
            }
        }
    }

    //spottr_login
    public function spottr_login()
    {
        try {
            //check nonce
            check_ajax_referer('spottr_nonce', 'spottr_nonce');
            //get data
            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);
            //check if email and password are not empty
            if (!empty($email) && !empty($password)) {
                //authenticate
                $response = Requests::post(SPOTTR_API_URL . 'auth/sign-in', array(
                    'Content-Type' => 'application/json',
                ), json_encode(array(
                    'identifier' => $email,
                    'password' => $password,
                )));
                //check if response is 200
                $data = json_decode($response->body);
                if ($response->status_code == 201) {
                    //set token
                    update_option('spottr_token', $data->data->token);
                    //set userid
                    update_option('spottr_userid', $data->data->user->id);
                    //set _pkt
                    update_option('spottr_pkt', $data->data->_pkt);
                    //check if not empty
                    if (!empty($data->data->_pkt)) {
                        //get public keys
                        $response = Requests::get(
                            SPOTTR_API_URL . 'public-keys?accessToken=' . $data->data->_pkt,
                            [
                                'Authorization' => 'Bearer ' . $data->data->token
                            ]
                        );
                        //check if response is 200
                        $data = json_decode($response->body, true);
                        if ($response->status_code == 200) {
                            //set public keys
                            $array = array(
                                'spottr_cloudname' => $data['data']['02'],
                                'spottr_cloudapi' => $data['data']['03'],
                                'spottr_cloud_secret' => $data['data']['05'],
                                'spottr_gapi' => $data['data']['07']
                            );
                            update_option('spottr_public_keys', $array);
                        }
                    }
                    //return success
                    wp_send_json(array(
                        'code' => 200,
                        'message' => 'Successfully authenticated, importing categories...',
                    ));
                } else {
                    //return error
                    wp_send_json(array(
                        'code' => 400,
                        'message' => $data->message
                    ));
                }
            } else {
                //return error
                wp_send_json(array(
                    'code' => 400,
                    'message' => 'Email and password are required',
                ));
            }
        } catch (\Exception $e) {
            //return error
            wp_send_json(array(
                'code' => 400,
                'message' => $e->getMessage(),
            ));
        }
    }

    //create spottr category
    public function importSpottrCat()
    {
        $params = [
            "page" => 1,
            "limit" => -1
        ];
        //request
        $response = Requests::get(
            SPOTTR_API_URL . 'categories' . '?' . http_build_query($params),
            array(
                'Content-Type' => 'application/json',
            )
        );
        $data = json_decode($response->body);
        if ($response->status_code == 200) {
            foreach ($data->data as $category) {
                //check if category slug exists
                $term = get_term_by('slug', $category->slug, 'product_cat');
                //remove with space
                $category->name = str_replace(' ', '', $category->name);
                if (!$term) {
                    //create category
                    $cat_id = wp_insert_term(
                        $category->name,
                        'product_cat',
                        array(
                            'description' => $category->description,
                            'slug' => $category->slug,
                        )
                    );
                    //check if category is created
                    if (!is_wp_error($cat_id)) {
                        //set category image
                        update_term_meta($cat_id['term_id'], 'spottr_term_image_url', $category->displayImage);
                        //set spottr id
                        update_term_meta($cat_id['term_id'], 'spottr_term_id', $category->id);
                    }
                } else {
                    //update category
                    $cat_id = wp_update_term(
                        $term->term_id,
                        'product_cat',
                        array(
                            'description' => $category->description,
                            'slug' => $category->slug,
                        )
                    );
                    //check if category is created
                    if (!is_wp_error($cat_id)) {
                        //set category image
                        update_term_meta($cat_id['term_id'], 'spottr_term_image_url', $category->displayImage);
                        //set spottr id
                        update_term_meta($cat_id['term_id'], 'spottr_term_id', $category->id);
                    }
                }
            }
        } else {
            //log error
            error_log($data->message);
            return false;
        }
    }

    //product_cat_column_content
    public function product_cat_column_content($content, $column_name, $term_id)
    {
        //use switch to add more columns
        switch ($column_name) {
            case 'thumbnail':
                $thumbnail = get_term_meta($term_id, 'spottr_term_image_url', true);
                if ($thumbnail) {
                    $value = '<img src="' . esc_url($thumbnail) . '" alt="" style="    height: 30px;"/>';
                } else {
                    $imageurl = esc_url(SPOTTR_PLUGIN_URL . 'assets/img/woocommerce-placeholder-324x324.png');
                    $value = '<img src="' . $imageurl . '" alt="" style="    height: 30px;"/>';
                }
                break;
        }
        return $value;
    }

    //product_cat_columns
    public function product_cat_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['thumbnail'] = __('Image', 'spottr');
        //unset thumb
        unset($columns['thumb']);
        return array_merge($new_columns, $columns);
    }

    //product_tag_columns
    public function product_tag_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['thumbnail'] = __('Image', 'spottr');
        //unset thumb
        unset($columns['thumb']);
        return array_merge($new_columns, $columns);
    }

    //product_tag_column_content
    public function product_tag_column_content($content, $column_name, $term_id)
    {
        //use switch to add more columns
        switch ($column_name) {
            case 'thumbnail':
                $thumbnail = get_term_meta($term_id, 'spottr_term_image_url', true);
                if ($thumbnail) {
                    $value = '<img src="' . esc_url($thumbnail) . '" alt="" style="    height: 30px;"/>';
                } else {
                    $imageurl = esc_url(SPOTTR_PLUGIN_URL . 'assets/img/woocommerce-placeholder-324x324.png');
                    $value = '<img src="' . $imageurl . '" alt="" style="    height: 30px;"/>';
                }
                break;
        }
        return $value;
    }

    //product_cat_edit_form_fields
    public function product_cat_edit_form_fields($tag)
    {
        $term_id = $tag->term_id;
        $spottr_term_id = get_term_meta($term_id, 'spottr_term_id', true);
        $spottr_term_image_url = get_term_meta($term_id, 'spottr_term_image_url', true);
        ?>
        <tr class="form-field   ">
            <th scope="row" valign="top"><label for="spottr_term_id"><?php _e('Spottr Term ID', 'spottr'); ?></label></th>
            <td>
                <input type="text" name="spottr_term_id" readonly id="spottr_term_id" value="<?php echo esc_html($spottr_term_id); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="spottr_term_image_url"><?php _e('Spottr Term Image', 'spottr'); ?></label></th>
            <td>
                <img src="<?php echo esc_html($spottr_term_image_url); ?>" alt="" style="    height: 30px;" />
            </td>
        </tr>
    <?php
    }

    //product_tag_edit_form_fields
    public function product_tag_edit_form_fields($tag)
    {
        $term_id = $tag->term_id;
        $spottr_term_id = get_term_meta($term_id, 'spottr_term_id', true);
        $spottr_term_image_url = get_term_meta($term_id, 'spottr_term_image_url', true);
    ?>
        <tr class="form-field   ">
            <th scope="row" valign="top"><label for="spottr_term_id"><?php _e('Spottr Term ID', 'spottr'); ?></label></th>
            <td>
                <input type="text" name="spottr_term_id" readonly id="spottr_term_id" value="<?php echo esc_html($spottr_term_id); ?>">
            </td>
        </tr>
        <tr class="form-field  ">
            <th scope="row" valign="top"><label for="spottr_term_image_url"><?php _e('Spottr Term Image', 'spottr'); ?></label></th>
            <td>
                <img src="<?php echo esc_url($spottr_term_image_url); ?>" alt="" style="    height: 30px;" />
            </td>
        </tr>
    <?php
    }

    //import spottr tags
    public function importSpottrTags()
    {
        $params = [
            "page" => 1,
            "limit" => -1
        ];
        //request
        $response = Requests::get(
            SPOTTR_API_URL . 'interests' . '?' . http_build_query($params),
            array(
                'Content-Type' => 'application/json',
            )
        );
        $data = json_decode($response->body);
        if ($response->status_code == 200) {
            foreach ($data->data as $tag) {
                //check if tag slug exists
                $term = get_term_by('slug', $tag->slug, 'product_tag');
                //remove with space
                $tag->name = str_replace(' ', '', $tag->name);
                if (!$term) {
                    //create tag
                    $tag_id = wp_insert_term(
                        $tag->name,
                        'product_tag',
                        array(
                            'description' => $tag->description,
                            'slug' => $tag->slug,
                        )
                    );
                    //check if tag is created
                    if (!is_wp_error($tag_id)) {
                        //set tag image
                        update_term_meta($tag_id['term_id'], 'spottr_term_image_url', $tag->displayImage);
                        //set spottr id
                        update_term_meta($tag_id['term_id'], 'spottr_term_id', $tag->id);
                    }
                } else {
                    //update tag
                    $tag_id = wp_update_term(
                        $term->term_id,
                        'product_tag',
                        array(
                            'description' => $tag->description,
                            'slug' => $tag->slug,
                        )
                    );
                    //check if tag is created
                    if (!is_wp_error($tag_id)) {
                        //set tag image
                        update_term_meta($tag_id['term_id'], 'spottr_term_image_url', $tag->displayImage);
                        //set spottr id
                        update_term_meta($tag_id['term_id'], 'spottr_term_id', $tag->id);
                    }
                }
            }
        } else {
            //log error
            error_log($data->message);
            return false;
        }
    }

    //spottr_content
    public function spottr_content()
    {
        try {
            //importSpottrCat
            $this->importSpottrCat();
            //importSpottrTags
            $this->importSpottrTags();
            //update option
            update_option('spottr_content_imported', true);
            //send response
            wp_send_json(array(
                'code' => 200,
                'message' => 'Content imported successfully',
            ));
        } catch (\Exception $e) {
            //log error
            error_log($e->getMessage());
            wp_send_json([
                'code' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    //spottr_disconnect
    public function spottr_disconnect()
    {
        try {
            //check nonce
            check_ajax_referer('spottr_nonce', 'spottr_nonce');
            //delete token
            delete_option('spottr_token');
            //delete userid
            delete_option('spottr_userid');
            //spottr_content_imported
            delete_option('spottr_content_imported');
            //spottr_pkt
            delete_option('spottr_pkt');
            //return success
            wp_send_json(array(
                'code' => 200,
                'message' => 'Successfully disconnected',
            ));
        } catch (\Exception $e) {
            //log error
            error_log($e->getMessage());
            wp_send_json([
                'code' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    //add_settings_link
    public static function add_settings_link($links)
    {
        $mylinks = array(
            '<a href="' . esc_url(admin_url('admin.php?page=spottr')) . '">Settings</a>',
        );
        return array_merge($links, $mylinks);
    }

    //add_admin_menu
    public function add_admin_menu()
    {
        add_menu_page(
            'Spottr',
            'Spottr',
            'manage_options',
            'spottr',
            array($this, 'menu_html'),
            'dashicons-cart',
            5
        );
    }

    //menu_html
    public function menu_html()
    {
        require SPOTTR_PLUGIN_DIR . 'templates/admin.php';
    }

    //enqueue_admin_styles
    public function enqueue_admin_styles()
    {
        wp_enqueue_style('spottr-admin', SPOTTR_PLUGIN_URL . 'assets/css/style.css', array(), time());
        //check if page is product edit.php?post_type=product
        if (isset($_GET['post_type']) && $_GET['post_type'] == 'product') {
            //inline css
            wp_add_inline_style('woocommerce_admin_styles', $this->inline_css());
        }
        //font awesome cdn
        wp_enqueue_style('spottr-admin-font-awesome', SPOTTR_PLUGIN_URL . 'assets/css/fontawesome.min.css', array(), time());
        //sweet alert cdn 
        wp_enqueue_style('spottr-admin-sweetalert', SPOTTR_PLUGIN_URL . 'assets/css/sweetalert2.min.css', array(), '10.15.7');
        //js
        wp_enqueue_script('spottr-admin-sweetalert', SPOTTR_PLUGIN_URL . 'assets/js/sweetalert2.min.js', array(
            'jquery',
        ), '10.15.7', true);
        //js
        wp_enqueue_script('spottr-admin-font-awesome-js', SPOTTR_PLUGIN_URL . 'assets/js/fontawesome.min.js', array(
            'jquery',
        ), time(), true);
        //js
        wp_enqueue_script('spottr-admin-js', SPOTTR_PLUGIN_URL . 'assets/js/spottr.js', array(
            'jquery',
        ), time(), true);
        //inline js
        wp_add_inline_script('spottr-admin-js', 'var spottr = ' . json_encode(array(
            'spottr_ajax_url' => admin_url('admin-ajax.php'),
            'spottr_nonce' => wp_create_nonce('spottr_nonce'),
        )), 'before');
    }

    //inline_css
    public function inline_css()
    {
    ?>
        <style>
            #spottr_product {
                width: 70px !important;
            }
        </style>
<?php
    }

    //deactivate
    public static function deactivate()
    {
        //delete token
        delete_option('spottr_token');
        //delete userid
        delete_option('spottr_userid');
        //spottr_content_imported
        delete_option('spottr_content_imported');
        //spottr_pkt
        delete_option('spottr_pkt');
    }
}

//init
$spottr = new Spottr();
$spottr->init();
