<?php
/**
 * Plugin Name: PureStitch WooCommerce AI Chatbot
 * Description: A smart AI-powered chatbot for WooCommerce that displays product information including name, stock, sizes, and prices.
 * Version: 2.0.0
 * Author: Eva Patel
 * Text Domain: purestitch-chatbot
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PureStitch_AI_Chatbot {
    
    // OpenAI API settings
    private $openai_api_key;
    private $openai_model = 'gpt-3.5-turbo';
    private $chat_history = array();
    private $max_history_length = 10;
    
    public function __construct() {
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Add the chatbot to the footer
        add_action('wp_footer', array($this, 'add_chatbot_to_footer'));
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
        
        // Add admin menu for settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Load settings
        $this->load_settings();
        
        // Initialize session for chat history
        add_action('init', array($this, 'start_session'));
    }
    
    /**
     * Start session to store chat history
     */
    public function start_session() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['purestitch_chat_history'])) {
            $_SESSION['purestitch_chat_history'] = array();
        }
        
        $this->chat_history = $_SESSION['purestitch_chat_history'];
    }
    
    /**
     * Load plugin settings
     */
    private function load_settings() {
        $this->openai_api_key = get_option('purestitch_openai_api_key', '');
        $this->openai_model = get_option('purestitch_openai_model', 'gpt-3.5-turbo');
        $this->max_history_length = get_option('purestitch_max_history_length', 10);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary directories if they don't exist
        $dirs = array(
            plugin_dir_path(__FILE__) . 'assets',
            plugin_dir_path(__FILE__) . 'assets/css',
            plugin_dir_path(__FILE__) . 'assets/js'
        );
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
        
        // Create default CSS file if it doesn't exist
        $css_file = plugin_dir_path(__FILE__) . 'assets/css/chatbot.css';
        if (!file_exists($css_file)) {
            $default_css = file_get_contents(plugin_dir_path(__FILE__) . 'assets/css/chatbot-default.css');
            file_put_contents($css_file, $default_css);
        }
        
        // Create default JS file if it doesn't exist
        $js_file = plugin_dir_path(__FILE__) . 'assets/js/chatbot.js';
        if (!file_exists($js_file)) {
            $default_js = file_get_contents(plugin_dir_path(__FILE__) . 'assets/js/chatbot-default.js');
            file_put_contents($js_file, $default_js);
        }
        
        // Add default settings
        add_option('purestitch_openai_api_key', '');
        add_option('purestitch_openai_model', 'gpt-3.5-turbo');
        add_option('purestitch_max_history_length', 10);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Add deactivation tasks if needed
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'PureStitch AI Chatbot Settings',
            'AI Chatbot',
            'manage_options',
            'purestitch-chatbot',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('purestitch_chatbot_settings', 'purestitch_openai_api_key');
        register_setting('purestitch_chatbot_settings', 'purestitch_openai_model');
        register_setting('purestitch_chatbot_settings', 'purestitch_max_history_length', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint'
        ));
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>PureStitch AI Chatbot Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('purestitch_chatbot_settings'); ?>
                <?php do_settings_sections('purestitch_chatbot_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="text" name="purestitch_openai_api_key" value="<?php echo esc_attr(get_option('purestitch_openai_api_key')); ?>" class="regular-text" />
                            <p class="description">Enter your OpenAI API key. <a href="https://platform.openai.com/account/api-keys" target="_blank">Get your API key</a></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">OpenAI Model</th>
                        <td>
                            <select name="purestitch_openai_model">
                                <option value="gpt-3.5-turbo" <?php selected(get_option('purestitch_openai_model'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php selected(get_option('purestitch_openai_model'), 'gpt-4'); ?>>GPT-4</option>
                                <option value="gpt-4-turbo" <?php selected(get_option('purestitch_openai_model'), 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                            </select>
                            <p class="description">Select the OpenAI model to use for the chatbot.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Max Chat History Length</th>
                        <td>
                            <input type="number" name="purestitch_max_history_length" value="<?php echo esc_attr(get_option('purestitch_max_history_length', 10)); ?>" min="1" max="20" />
                            <p class="description">Maximum number of messages to keep in chat history for context.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on product pages and shop pages
        if (is_product() || is_shop() || is_product_category() || is_front_page() || is_home()) {
            // Enqueue chatbot styles
            wp_enqueue_style(
                'purestitch-chatbot-style',
                plugin_dir_url(__FILE__) . 'assets/css/chatbot.css',
                array(),
                '2.0.0'
            );
            
            // Enqueue chatbot scripts
            wp_enqueue_script(
                'purestitch-chatbot-script',
                plugin_dir_url(__FILE__) . 'assets/js/chatbot.js',
                array('jquery'),
                '2.0.0',
                true
            );
            
            // Pass data to the script
            wp_localize_script(
                'purestitch-chatbot-script',
                'purestitchChatbot',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'rest_url' => esc_url_raw(rest_url('purestitch-chatbot/v1/')),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'site_name' => get_bloginfo('name'),
                    'is_ai_enabled' => !empty($this->openai_api_key) ? 'yes' : 'no'
                )
            );
        }
    }
    
    /**
     * Add chatbot HTML to footer
     */
    public function add_chatbot_to_footer() {
        // Only show on product pages and shop pages
        if (is_product() || is_shop() || is_product_category() || is_front_page() || is_home()) {
            ?>
            <!-- PureStitch AI Chatbot Container -->
            <div id="purestitch-chatbot-container" class="purestitch-chatbot-container">
                <!-- Chatbot Icon -->
                <div id="purestitch-chatbot-icon" class="purestitch-chatbot-icon">
                    <svg viewBox="0 0 24 24" width="32" height="32">
                        <path fill="currentColor" d="M12,2C6.477,2,2,6.477,2,12c0,5.523,4.477,10,10,10s10-4.477,10-10C22,6.477,17.523,2,12,2z M6,14h2v2H6V14z M6,12h8v1H6V12z M16,14h2v2h-2V14z M18,11h-8v-1h8V11z M8,8h8v1H8V8z"></path>
                    </svg>
                </div>
                
                <!-- Chatbot Dialog -->
                <div id="purestitch-chatbot-dialog" class="purestitch-chatbot-dialog" style="display: none;">
                    <div class="purestitch-chatbot-header">
                        <h3>PureStitch AI Assistant</h3>
                        <button id="purestitch-chatbot-close" class="purestitch-chatbot-close">&times;</button>
                    </div>
                    
                    <div id="purestitch-chatbot-messages" class="purestitch-chatbot-messages">
                        <div class="purestitch-chatbot-message bot">
                            Hello! I'm your AI shopping assistant. How can I help you find the perfect products today?
                        </div>
                    </div>
                    
                    <div class="purestitch-chatbot-input-container">
                        <input type="text" id="purestitch-chatbot-input" class="purestitch-chatbot-input" placeholder="Ask about products...">
                        <button id="purestitch-chatbot-send" class="purestitch-chatbot-send">Send</button>
                    </div>
                    <div class="purestitch-chatbot-typing" style="display: none;">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_rest_endpoints() {
        register_rest_route('purestitch-chatbot/v1', '/query', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_chatbot_query'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('purestitch-chatbot/v1', '/product/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product_details'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('purestitch-chatbot/v1', '/clear-history', array(
            'methods' => 'POST',
            'callback' => array($this, 'clear_chat_history'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Clear chat history
     */
    public function clear_chat_history() {
        $this->chat_history = array();
        $_SESSION['purestitch_chat_history'] = array();
        
        return rest_ensure_response(array(
            'message' => 'Chat history cleared',
            'success' => true
        ));
    }
    
    /**
     * Add message to chat history
     */
    private function add_to_chat_history($role, $content) {
        // Add message to chat history
        $this->chat_history[] = array(
            'role' => $role,
            'content' => $content
        );
        
        // Keep history within limits
        if (count($this->chat_history) > $this->max_history_length) {
            array_shift($this->chat_history);
        }
        
        // Update session
        $_SESSION['purestitch_chat_history'] = $this->chat_history;
    }
    
    /**
     * Get store information for AI context
     */
    private function get_store_context() {
        $store_name = get_bloginfo('name');
        $store_description = get_bloginfo('description');
        
        // Get product categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'number' => 10
        ));
        
        $category_names = array();
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
        }
        
        // Get information about a few products
        $products_query = new WP_Query(array(
            'post_type' => 'product',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $products_info = array();
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products_info[] = array(
                        'name' => $product->get_name(),
                        'price' => strip_tags($product->get_price_html()),
                        'categories' => wp_get_post_terms(get_the_ID(), 'product_cat', array('fields' => 'names'))
                    );
                }
            }
            wp_reset_postdata();
        }
        
        // Build context string
        $context = "You are a helpful AI shopping assistant for the online store '{$store_name}'";
        if (!empty($store_description)) {
            $context .= ", which is described as: '{$store_description}'";
        }
        $context .= ".\n\n";
        
        if (!empty($category_names)) {
            $context .= "The store has these product categories: " . implode(', ', $category_names) . ".\n\n";
        }
        
        if (!empty($products_info)) {
            $context .= "Here are some example products in the store:\n";
            foreach ($products_info as $product) {
                $product_categories = !empty($product['categories']) ? implode(', ', $product['categories']) : 'No category';
                $context .= "- {$product['name']} (Price: {$product['price']}, Categories: {$product_categories})\n";
            }
            $context .= "\n";
        }
        
        $context .= "Your job is to help customers find products they're looking for, answer questions about products, and provide a friendly shopping experience. You can search for products when needed, and your responses should be helpful, concise, and friendly.\n\n";
        $context .= "You can use the search_products function when a customer is looking for specific products. Don't make up information about products that don't exist in the store.\n\n";
        
        return $context;
    }
    
    /**
     * Call OpenAI API
     */
    private function call_openai_api($user_query, $store_data = array()) {
        if (empty($this->openai_api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key is not configured. Please contact the store administrator.'
            );
        }
        
        // Get store context
        $store_context = $this->get_store_context();
        
        // Prepare the messages array with system prompt
        $messages = array(
            array(
                'role' => 'system',
                'content' => $store_context
            )
        );
        
        // Add chat history
        foreach ($this->chat_history as $message) {
            $messages[] = $message;
        }
        
        // Add the current user query
        $messages[] = array(
            'role' => 'user',
            'content' => $user_query
        );
        
        // Define available functions
        $functions = array(
            array(
                'name' => 'search_products',
                'description' => 'Search for products in the store by keywords, categories, or attributes',
                'parameters' => array(
                    'type' => 'object',
                    'properties' => array(
                        'search_term' => array(
                            'type' => 'string',
                            'description' => 'The search term to look for products'
                        )
                    ),
                    'required' => array('search_term')
                )
            ),
            array(
                'name' => 'get_product_details',
                'description' => 'Get detailed information about a specific product',
                'parameters' => array(
                    'type' => 'object',
                    'properties' => array(
                        'product_id' => array(
                            'type' => 'integer',
                            'description' => 'The ID of the product to get details for'
                        )
                    ),
                    'required' => array('product_id')
                )
            )
        );
        
        // Call OpenAI API
        $request_body = array(
            'model' => $this->openai_model,
            'messages' => $messages,
            'tools' => array(
                array(
                    'type' => 'function',
                    'functions' => $functions
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 500
        );
        
        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->openai_api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($request_body),
                'timeout' => 30
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 200 || empty($response_body)) {
            return array(
                'success' => false,
                'message' => 'API request failed with status code ' . $response_code
            );
        }
        
        // Check if there's a function call
        $ai_message = $response_body['choices'][0]['message'];
        
        // Handle function calling if available
        if (isset($ai_message['tool_calls']) && !empty($ai_message['tool_calls'])) {
            $function_call = $ai_message['tool_calls'][0];
            $function_name = $function_call['function']['name'];
            $function_args = json_decode($function_call['function']['arguments'], true);
            
            $function_response = null;
            
            // Execute the appropriate function
            if ($function_name === 'search_products' && isset($function_args['search_term'])) {
                $function_response = $this->search_products($function_args['search_term']);
            } elseif ($function_name === 'get_product_details' && isset($function_args['product_id'])) {
                $function_response = $this->get_product_details_for_ai($function_args['product_id']);
            }
            
            // If we have a function response, send it back to OpenAI for processing
            if ($function_response) {
                // Add the AI's function call request to the message history
                $this->add_to_chat_history('assistant', $ai_message['content']);
                
                // Add the function response to messages
                $messages[] = array(
                    'role' => 'assistant',
                    'content' => $ai_message['content'],
                    'tool_calls' => $ai_message['tool_calls']
                );
                
                $messages[] = array(
                    'role' => 'tool',
                    'tool_call_id' => $function_call['id'],
                    'name' => $function_name,
                    'content' => json_encode($function_response)
                );
                
                // Send a follow-up request to OpenAI to interpret the function results
                $request_body = array(
                    'model' => $this->openai_model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 500
                );
                
                $follow_up_response = wp_remote_post(
                    'https://api.openai.com/v1/chat/completions',
                    array(
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $this->openai_api_key,
                            'Content-Type' => 'application/json'
                        ),
                        'body' => json_encode($request_body),
                        'timeout' => 30
                    )
                );
                
                if (!is_wp_error($follow_up_response)) {
                    $follow_up_body = json_decode(wp_remote_retrieve_body($follow_up_response), true);
                    
                    if (!empty($follow_up_body['choices'][0]['message']['content'])) {
                        $ai_response = $follow_up_body['choices'][0]['message']['content'];
                        
                        // Add this to chat history
                        $this->add_to_chat_history('assistant', $ai_response);
                        
                        return array(
                            'success' => true,
                            'message' => $ai_response,
                            'type' => 'ai_response',
                            'function_used' => $function_name,
                            'function_data' => $function_response
                        );
                    }
                }
            }
        } else {
            // Regular text response without function call
            $ai_response = $ai_message['content'];
            
            // Add this to chat history
            $this->add_to_chat_history('assistant', $ai_response);
            
            return array(
                'success' => true,
                'message' => $ai_response,
                'type' => 'ai_response'
            );
        }
        
        // Fallback response if something went wrong
        return array(
            'success' => false,
            'message' => "I'm having trouble connecting to my brain right now. Could you try again in a moment?"
        );
    }
    
    /**
     * Process chatbot queries
     */
    public function process_chatbot_query($request) {
        $params = $request->get_params();
        $query = sanitize_text_field($params['query'] ?? '');
        
        if (empty($query)) {
            return new WP_Error('missing_query', 'Query is required', array('status' => 400));
        }
        
        // Log the query for debugging
        error_log('Chatbot query: ' . $query);
        
        // Add user query to chat history
        $this->add_to_chat_history('user', $query);
        
        // If AI is enabled, use it
        if (!empty($this->openai_api_key)) {
            $ai_response = $this->call_openai_api($query);
            
            if ($ai_response['success']) {
                return rest_ensure_response($ai_response);
            } else {
                // If AI fails, fall back to the basic search
                error_log('AI failed, falling back to basic search: ' . $ai_response['message']);
            }
        }
        
        // Fall back to the original logic if AI is not configured or fails
        $query_lower = strtolower($query);
        
        // Check if this is a greeting or general question
        if ($this->is_greeting($query_lower)) {
            return rest_ensure_response(array(
                'message' => "Hello! I'm here to help you find products. What are you looking for today?",
                'type' => 'general'
            ));
        }
        
        // Check if this is a request for latest products or browsing
        if ($this->is_browse_request($query_lower)) {
            $product_list = $this->get_product_list();
            return rest_ensure_response($product_list);
        }
        
        // Check if this is a product search
        if (strpos($query_lower, 'product') !== false || 
            strpos($query_lower, 'price') !== false ||
            strpos($query_lower, 'stock') !== false ||
            strpos($query_lower, 'size') !== false ||
            strpos($query_lower, 'do you have') !== false ||
            strpos($query_lower, 'looking for') !== false ||
            strpos($query_lower, 'find') !== false ||
            strpos($query_lower, 'search') !== false ||
            strlen($query) > 3) {
            
            $product_search = $this->search_products($query);
            
            if (!empty($product_search)) {
                return rest_ensure_response($product_search);
            }
        }
        
        // Default response if no specific query matches
        return rest_ensure_response(array(
            'message' => "I can help you find information about our products, including prices, sizes, and availability. Try asking about a specific product or browsing our collection.",
            'type' => 'general'
        ));
    }
    
    /**
     * Check if query is a greeting
     */
    private function is_greeting($query) {
        $greetings = array(
            'hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening',
            'howdy', 'hola', 'greetings'
        );
        
        foreach ($greetings as $greeting) {
            if (strpos($query, $greeting) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query is a request to browse products
     */
    private function is_browse_request($query) {
        $browse_terms = array(
            'browse', 'show me products', 'show products', 'latest products',
            'new products', 'all products', 'what do you have', 'what products',
            'product list', 'catalog', 'browse products'
        );
        
        foreach ($browse_terms as $term) {
            if (strpos($query, $term) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Search for products based on query
     */
    private function search_products($query) {
        // Clean and prepare the search term
        $search_term = strtolower(trim($query));
        
        // Log for debugging
        error_log("Chatbot searching for: " . $search_term);
        
        // Handle special case for "latest products" or "browse products"
        if (strpos($search_term, "latest product") !== false || 
            strpos($search_term, "browse product") !== false ||
            $search_term == "browse our latest products") {
            return $this->get_product_list();
        }
        
        // Remove common words that might confuse the search
        $search_term = preg_replace('/\b(do you have|have|any|the|a|an|in|for|with|set of|set|price|cost|stock|available)\b/i', '', $search_term);
        $search_term = trim($search_term);
        
        // Create a more robust search
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
            's'              => $search_term
        );
        
        // If search term is too short, get popular products
        if (empty($search_term) || strlen($search_term) < 3) {
            return $this->get_product_list();
        }
        
        // Run the initial product search
        $products_query = new WP_Query($args);
        error_log("Initial search found " . $products_query->post_count . " products");
        
        if ($products_query->have_posts()) {
            $products = $this->format_products_from_query($products_query);
            
            return array(
                'message' => "Here are products that match your query:",
                'type' => 'product_list',
                'products' => $products
            );
        }
        
        // If no results, try a more flexible search by splitting the query into words
        $search_words = explode(' ', $search_term);
        $search_words = array_filter($search_words, function($word) {
            return strlen($word) > 2;
        });
        
        if (empty($search_words)) {
            return $this->get_product_list();
        }
        
        // Try to match against product title and content directly using custom SQL
        global $wpdb;
        $like_patterns = array();
        foreach ($search_words as $word) {
            if (strlen($word) > 2) {
                $like_patterns[] = $wpdb->prepare("post_title LIKE %s OR post_content LIKE %s", 
                    '%' . $wpdb->esc_like($word) . '%', 
                    '%' . $wpdb->esc_like($word) . '%'
                );
            }
        }
        
        if (!empty($like_patterns)) {
            $sql = "SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type = 'product' 
                    AND post_status = 'publish' 
                    AND (" . implode(' OR ', $like_patterns) . ")
                    LIMIT 5";
            
            $product_ids = $wpdb->get_col($sql);
            
            if (!empty($product_ids)) {
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => 5,
                    'post_status'    => 'publish',
                    'post__in'       => $product_ids
                );
                
                $products_query = new WP_Query($args);
                $products = $this->format_products_from_query($products_query);
                
                return array(
                    'message' => "Here are products that might interest you:",
                    'type' => 'product_list',
                    'products' => $products
                );
            }
        }
        
        // If still no results, try searching in product categories
        $product_cats = get_terms(array(
            'taxonomy' => 'product_cat',
            'name__like' => $search_term
        ));
        
        if (!empty($product_cats) && !is_wp_error($product_cats)) {
            $category_ids = wp_list_pluck($product_cats, 'term_id');
            
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => 5,
                'post_status'    => 'publish',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $category_ids
                    )
                )
            );
            
            $products_query = new WP_Query($args);
            
            if ($products_query->have_posts()) {
                $products = $this->format_products_from_query($products_query);
                
                return array(
                    'message' => "Here are products from categories that match your query:",
                    'type' => 'product_list',
                    'products' => $products
                );
            }
        }
        
        // Final fallback: search by attributes/tags
        $product_tags = get_terms(array(
            'taxonomy' => 'product_tag',
            'name__like' => $search_term
        ));
        
        if (!empty($product_tags) && !is_wp_error($product_tags)) {
            $tag_ids = wp_list_pluck($product_tags, 'term_id');
            
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => 5,
                'post_status'    => 'publish',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_tag',
                        'field'    => 'term_id',
                        'terms'    => $tag_ids
                    )
                )
            );
            
            $products_query = new WP_Query($args);
            
            if ($products_query->have_posts()) {
                $products = $this->format_products_from_query($products_query);
                
                return array(
                    'message' => "Here are products tagged with terms related to your query:",
                    'type' => 'product_list',
                    'products' => $products
                );
            }
        }
        
        // If no products found after all attempts
        return array(
            'message' => "I couldn't find any products matching your query. Would you like to browse our latest products instead?",
            'type' => 'general',
            'suggest_browse' => true
        );
    }
    
    /**
     * Format products from a WP_Query result
     */
    private function format_products_from_query($query) {
        $products = array();
        
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            
            if ($product) {
                $thumbnail_id = $product->get_image_id();
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'thumbnail') : wc_placeholder_img_src('thumbnail');
                
                $product_info = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'price' => strip_tags($product->get_price_html()),
                    'url' => get_permalink($product_id),
                    'image' => $thumbnail_url,
                    'stock_status' => $product->get_stock_status(),
                    'short_description' => $product->get_short_description()
                );
                
                // Add variation info if it's a variable product
                if ($product->is_type('variable')) {
                    $variations = $product->get_available_variations();
                    $variation_attributes = array();
                    
                    if (!empty($variations)) {
                        foreach ($variations as $variation) {
                            $attributes = array();
                            foreach ($variation['attributes'] as $key => $value) {
                                $taxonomy = str_replace('attribute_', '', $key);
                                $term = get_term_by('slug', $value, $taxonomy);
                                $attribute_name = wc_attribute_label($taxonomy);
                                $attribute_value = $term ? $term->name : $value;
                                
                                if (!isset($variation_attributes[$attribute_name])) {
                                    $variation_attributes[$attribute_name] = array();
                                }
                                if (!empty($attribute_value) && !in_array($attribute_value, $variation_attributes[$attribute_name])) {
                                    $variation_attributes[$attribute_name][] = $attribute_value;
                                }
                            }
                        }
                    }
                    
                    $product_info['variations'] = $variation_attributes;
                }
                
                $products[] = $product_info;
            }
        }
        
        wp_reset_postdata();
        return $products;
    }
    
    /**
     * Get a list of latest products
     */
    private function get_product_list() {
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        );
        
        $products_query = new WP_Query($args);
        
        if ($products_query->have_posts()) {
            $products = $this->format_products_from_query($products_query);
            
            return array(
                'message' => "Here are our latest products:",
                'type' => 'product_list',
                'products' => $products
            );
        }
        
        return array(
            'message' => "I couldn't find any products in the store. Please check back later.",
            'type' => 'general'
        );
    }
    
    /**
     * Get product details for display in the chatbot
     */
    public function get_product_details($request) {
        $product_id = intval($request['id']);
        
        if (!$product_id) {
            return new WP_Error('invalid_product', 'Invalid product ID', array('status' => 400));
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('product_not_found', 'Product not found', array('status' => 404));
        }
        
        $thumbnail_id = $product->get_image_id();
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : wc_placeholder_img_src('medium');
        
        $gallery_image_ids = $product->get_gallery_image_ids();
        $gallery_images = array();
        
        foreach ($gallery_image_ids as $gallery_image_id) {
            $gallery_image_url = wp_get_attachment_image_url($gallery_image_id, 'thumbnail');
            if ($gallery_image_url) {
                $gallery_images[] = $gallery_image_url;
            }
        }
        
        $product_details = array(
            'id' => $product_id,
            'name' => $product->get_name(),
            'price' => strip_tags($product->get_price_html()),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'url' => get_permalink($product_id),
            'image' => $thumbnail_url,
            'gallery' => $gallery_images,
            'stock_status' => $product->get_stock_status(),
            'in_stock' => $product->is_in_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'short_description' => $product->get_short_description(),
            'description' => $product->get_description(),
            'categories' => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
            'tags' => wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names')),
            'average_rating' => $product->get_average_rating(),
            'review_count' => $product->get_review_count()
        );
        
        // Handle variations if it's a variable product
        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            $variation_details = array();
            
            foreach ($variations as $variation) {
                $variation_obj = wc_get_product($variation['variation_id']);
                
                if ($variation_obj) {
                    $attributes = array();
                    foreach ($variation['attributes'] as $key => $value) {
                        $taxonomy = str_replace('attribute_', '', $key);
                        $term = get_term_by('slug', $value, $taxonomy);
                        $attribute_name = wc_attribute_label($taxonomy);
                        $attribute_value = $term ? $term->name : $value;
                        
                        $attributes[$attribute_name] = $attribute_value;
                    }
                    
                    $variation_details[] = array(
                        'id' => $variation['variation_id'],
                        'attributes' => $attributes,
                        'price' => strip_tags($variation_obj->get_price_html()),
                        'is_in_stock' => $variation_obj->is_in_stock(),
                        'stock_quantity' => $variation_obj->get_stock_quantity()
                    );
                }
            }
            
            $product_details['variations'] = $variation_details;
        }
        
        return rest_ensure_response($product_details);
    }
    
    /**
     * Get product details for AI processing
     */
    private function get_product_details_for_ai($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return array(
                'error' => 'Product not found',
                'product_id' => $product_id
            );
        }
        
        $product_details = array(
            'id' => $product_id,
            'name' => $product->get_name(),
            'price' => strip_tags($product->get_price_html()),
            'url' => get_permalink($product_id),
            'stock_status' => $product->get_stock_status(),
            'in_stock' => $product->is_in_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'short_description' => $product->get_short_description(),
            'categories' => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
        );
        
        // Handle variations if it's a variable product
        if ($product->is_type('variable')) {
            $attributes = $product->get_variation_attributes();
            $formatted_attributes = array();
            
            foreach ($attributes as $attribute_name => $values) {
                $attribute_label = wc_attribute_label($attribute_name);
                $formatted_attributes[$attribute_label] = array_values($values);
            }
            
            $product_details['attributes'] = $formatted_attributes;
        }
        
        return $product_details;
    }
}

// Initialize the plugin
new PureStitch_AI_Chatbot();
        
        