<h1>AJAX Favorite Posts / Wishlist Worpress Plugin</h1>

<p>by <a href="http://mariancerny.com/" title="Marian Cerny">Marian Cerny</a></p>

<p>This Wordpress plugin lets you easily add a favorite posts or wishlist functionality to your Wordpress website. Works with any post type. The plugin uses the database if user is logged in, or cookies if logged out.</p>

<h2>Usage</h2>

<h3>Displaying the favorite link</h3>
<p>
In your loop, simply call the <strong><code>mc_afwl_get_link()</code></strong> function where you want the favorite link to display. This function takes one required and one optional parameter:
</p>

<p>
<strong>Post ID</strong> - The ID of the current post, that will be added as a favorite.
</p>
<p>
<strong>Link texts</strong> - An array of link texts to display in the link. These can also be images, or you can use any HTML mark-up. The key in the array should be 'add', 'remove', 'add_title' and 'remove_title', 
</p>

<h3>Displaying the favorite posts</h3>

<p>
To retrieve a list of favorite post ID's, use the <strong><code>mc_afwl_get_items()</code></strong> function. This will return an array of integers, which you can then use as a parameter in your query. Just add the 'post__in' parameter with the array returned by this function and then use the loop to display the favorite posts.
</p>

<h3>Other functions</h3>

<p>
<strong><code>mc_afwl_clear()</code></strong> - clears all favorites
</p>
<p>
<strong><code>mc_afwl_get_count()</code></strong> - returns the number of favorite items in a <code>&lt;span&gt;</code> element. The value will change automatically as favorite items are added and removed.
</p>