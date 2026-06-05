import { safePhpUnserialize } from './utils'

const bigS = `a:4:{s:8:"template";s:13:"blocksy-child";s:8:"site_url";s:18:"http://51443.local";s:4:"mods";a:9:{i:0;b:0;s:18:"nav_menu_locations";a:0:{}s:18:"custom_css_post_id";i:16;s:8:"facebook";s:47:"https://facebook.com/PinnacleRealEstateAcademy/";s:9:"instagram";s:48:"https://instagram.com/pinnaclerealestateacademy/";s:7:"twitter";s:26:"https://twitter.com/PREAsc";s:4:"yelp";s:62:"https://yelp.com/biz/pinnacle-real-estate-academy-myrtle-beach";s:7:"youtube";s:56:"https://www.youtube.com/channel/UC-FBHornBB7EULvMFsEvEwg";s:25:"header_after_body_scripts";s:344:"<script type="text/javascript">
  window._mfq = window._mfq || [];
  (function() {
    var mf = document.createElement("script");
    mf.type = "text/javascript"; mf.defer = true;
    mf.src = "//cdn.mouseflow.com/projects/70f847af-74e3-49ae-a1fd-4e18346eebf3.js";
    document.getElementsByTagName("head")[0].appendChild(mf);
  })();
</script>";}s:6:"wp_css";s:212:".ct-shortcuts-bar { left: 35%; right: 10%; }

.acsb-trigger{
box-shadow: 0px 3px 5px 0px;
}

.ct-service-box:hover p,
.ct-service-box:hover h3,
.ct-service-box:hover .fas {
	color: #fff;
	transition: color .3s;
}";}
`

describe('safePhpUnserialize', () => {
	it('should correctly unserialize a simple PHP serialized string', () => {
		const serialized = 's:11:"Hello World";'
		const result = safePhpUnserialize(serialized)

		expect(result).toBe('Hello World')
	})

	it('should correctly unserialize a PHP serialized array', () => {
		const serialized = 'a:3:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;s:3:"baz";}'
		const result = safePhpUnserialize(serialized)

		expect(result).toEqual(['foo', 'bar', 'baz'])
	})

	it('should correctly unserialize a PHP serialized object', () => {
		const result = safePhpUnserialize(bigS)

		expect(result.mods).toBeTruthy()
	})

	it('should correctl handle string with incorrect length', () => {
		// it's supposed to be 11, but it's actually 15
		const serialized = 's:15:"Hello World";'

		const result = safePhpUnserialize(serialized)

		expect(result).toEqual('Hello World')
	})
})
