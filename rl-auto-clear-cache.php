<?php

/*
 * Plugin Name: RedeLivre Auto Clear Cache
 * Plugin URI: https://github.com/redelivre/rl-auto-clear-cache
 * Description: Clear Cache from W3 and CloudFlare
 * Version: 0.0.1-beta.1
 * Author: RedeLivre
 * Author URI: http://redelivre.org.br
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
// TODO find code source base for blame
namespace RedeLivre;

class AutoClearCache {
	function __construct() {
		add_action( 'admin_init', array($this, 'admin_init'));
		add_action( 'init', array($this, 'init'));
	}
	
	public function init() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		
		if (!is_user_logged_in() ) {
			header( 'x-HTML-Edge-Cache: bypass-cookies=wp-|wordpress|comment_|woocommerce_' );
		} else {
			header( 'x-HTML-Edge-Cache: nocache' );
		}
	}
	
	public function admin_init() {
		add_action ( 'transition_post_status', array($this, 'post_status'), 99, 3 );
		add_action ( 'dm_handle_actions_init', array($this, 'dm_handle_actions_init'), 99, 1 ); // handle domain mapping
		add_action ( 'updated_mapsvg_meta', array($this, 'updated_mapsvg_meta'), 99, 4 ); // handle domain mapping
	}
	
	public function post_status($new_status, $old_status, $post) {
		$custom_post_types = array (
				"news" => "news", // Theme post type news for redeninja
				"pauta" => "pautas", // Plugin Delibera post type
				"post" => "posts",
				"page" => "pages",
				"attachment" => "attachment",
				"revision" => "revision",
				"project" => 'projects',
				'agenda' => 'agenda',
				'event' => 'events',
				'forminator_forms' => 'forminator_forms', // Forminator
				'forminator_polls' => 'forminator_polls', // Forminator
				'forminator_quizzes' => 'forminator_quizzes' // Forminator
		);
		if (array_key_exists ( $post->post_type, $custom_post_types ) && ($new_status === "publish" || $old_status === "publish")) {
			$this->clear($post);
		}
	}
	
	public function dm_handle_actions_init($domain) {
		$this->clear();
	}
	
	protected function clear($post = null) {
		// TODO not clear all cache, only for the current post and archive
		if (defined ( 'W3TC' )) {
			// Flush everything!
			w3tc_flush_all ();
		}
		if (defined ( 'CLOUDFLARE_PLUGIN_DIR' )) {
			$CF_Hooks = new \CF\WordPress\Hooks ();
			$CF_Hooks->purgeCacheEverything ();
		}
	}
	
	/**
	 * Clear when change mapsvg config
	 * 
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param string $_meta_value
	 */
	public function updated_mapsvg_meta($meta_id, $object_id, $meta_key, $_meta_value) {
		$this->clear($object_id);
	}
	
	
}

new \RedeLivre\AutoClearCache ();