<?php   
    class Meta extends Modules {
        public function __init() {
           $this->addAlias("meta", "insert_meta");
        }
        
        static function __install() {                         
            $config = Config::current();
            $config->set("meta_locale", "en_US");
            $config->set("meta_facebook", "0");
            $config->set("meta_facebook_url", "https://www.facebook.com/[facebook-name]");
            $config->set("meta_facebook_ids", "");
            $config->set("meta_twitter", "0");
            $config->set("meta_twitter_site", "@[twitter-name]");
            $config->set("meta_default_image", "");
        }
        
        static function __uninstall() {            
            $config = Config::current();
            $config->remove("meta_locale");
            $config->remove("meta_facebook");
            $config->remove("meta_facebook_url");
            $config->remove("meta_facebook_ids");
            $config->remove("meta_twitter");
            $config->remove("meta_twitter_site");
            $config->remove("meta_default_image");
        }
        
        static function settings_nav($navs) {
            if (Visitor::current()->group->can("change_settings"))
                $navs["meta_settings"] = array("title" => __("Meta", "meta"));

            return $navs;
        }
        
        static function admin_meta_settings($admin) {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            if (empty($_POST))
                return $admin->display("meta_settings");

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));
                
            if (isset($_FILES['meta_default_image']) and $_FILES['meta_default_image']['error'] == 0)
                $default_image = upload($_FILES['meta_default_image'], array("jpg", "jpeg", "png", "gif", "bmp"));
            
            $config = Config::current();
            
            $set = array($config->set("meta_facebook", isset($_POST['meta_facebook'])),
                         $config->set("meta_facebook_url", $_POST['meta_facebook_url']),
                         $config->set("meta_facebook_ids", $_POST['meta_facebook_ids']),
                         $config->set("meta_twitter", isset($_POST['meta_twitter'])),
                         $config->set("meta_twitter_site", $_POST['meta_twitter_site']),
                         $config->set("meta_default_image", $default_image));

            if (!in_array(false, $set))
                Flash::notice(__("Settings updated."), "/admin/?action=meta_settings");
        }
        
        private function clean_and_truncate($val)
        {
            return truncate(preg_replace('/^\s+|\n|\r|\s+$/m', '', strip_tags($val)), 200);
        }
        
        public function insert_meta($post, $page) {
            $meta_template = MODULES_DIR."/meta/meta.twig";
            if (!file_exists($meta_template))
                return;
            
            $cache = (is_writable(INCLUDES_DIR."/caches") and
                      !DEBUG and
                      !PREVIEWING and
                      !defined('CACHE_TWIG') or CACHE_TWIG);
            
            $twig = new Twig_Loader(THEME_DIR,
                                    $cache ?
                                    INCLUDES_DIR."/caches" :
                                    null);
            
            $config = Config::current();
            $theme = Theme::current();
            $feathers = Feathers::$instances;
            
            $meta = array();
            $meta["title"] = oneof($theme->title, $config->name);
            $meta["description"] = $config->description;
            $meta["url"] = $config->chyrp_url.$_SERVER['REQUEST_URI'];
            
            if (isset($config->meta_default_image))
                $meta["image"] = $config->chyrp_url.$config->uploads_path.urlencode($config->meta_default_image);
            
            // check for post view and overwrite meta-data
            if ( Route::current()->action == "view" && isset($post) ) {
                $meta["title"] = oneof($post->headline, $post->title, $theme->title, $config->name);
                $meta["description"] = oneof($this->clean_and_truncate($post->body), $this->clean_and_truncate($post->description), $this->clean_and_truncate($post->caption), $this->clean_and_truncate($post->quote), $config->description);
                $meta["url"] = $post->url();
                
                if ($post->feather == 'article')
                    $meta["image"] = $feathers['article']->hero_url($post);
                elseif ($post->feather == 'photo')
                    $meta["image"] = $feathers['photo']->image_url($post);
            
            // check for page view and overwrite meta-data            
            } elseif ( Route::current()->action == "page" && isset($page) ) {
                $meta["title"] = oneof($page->title, $theme->title, $config->name);
                $meta["description"] = oneof($this->clean_and_truncate($page->body), $config->description);
                $meta["url"] = $page->url();
            }
            
            $context = array();
            $context["site"] = $config;
            $context["meta"] = $meta;
            
            return $twig->getTemplate($meta_template)->render($context);
        }
    }
