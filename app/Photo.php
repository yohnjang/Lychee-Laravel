<?php
/** @noinspection PhpUndefinedClassInspection */

namespace App;

use App\ModelFunctions\Helpers;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * App\Photo
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $url
 * @property string $tags
 * @property int $public
 * @property int $owner_id
 * @property string $type
 * @property int|null $width
 * @property int|null $height
 * @property string $size
 * @property string $iso
 * @property string $aperture
 * @property string $make
 * @property string $model
 * @property string $lens
 * @property string $shutter
 * @property string $focal
 * @property float|null $latitude
 * @property float|null $longitude
 * @property float|null $altitude
 * @property Carbon|null $takestamp
 * @property int $star
 * @property string $thumbUrl
 * @property int|null $album_id
 * @property string $checksum
 * @property string $license
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $medium
 * @property string $medium2x
 * @property string $small
 * @property string $small2x
 * @property int $thumb2x
 * @property-read Album|null $album
 * @property-read User $owner
 * @method static Builder|Photo newModelQuery()
 * @method static Builder|Photo newQuery()
 * @method static Builder|Photo ownedBy($id)
 * @method static Builder|Photo public ()
 * @method static Builder|Photo query()
 * @method static Builder|Photo recent()
 * @method static Builder|Photo stars()
 * @method static Builder|Photo unsorted()
 * @method static Builder|Photo whereAlbumId($value)
 * @method static Builder|Photo whereAltitude($value)
 * @method static Builder|Photo whereAperture($value)
 * @method static Builder|Photo whereChecksum($value)
 * @method static Builder|Photo whereCreatedAt($value)
 * @method static Builder|Photo whereDescription($value)
 * @method static Builder|Photo whereFocal($value)
 * @method static Builder|Photo whereHeight($value)
 * @method static Builder|Photo whereId($value)
 * @method static Builder|Photo whereIso($value)
 * @method static Builder|Photo whereLatitude($value)
 * @method static Builder|Photo whereLens($value)
 * @method static Builder|Photo whereLicense($value)
 * @method static Builder|Photo whereLongitude($value)
 * @method static Builder|Photo whereMake($value)
 * @method static Builder|Photo whereMedium($value)
 * @method static Builder|Photo whereMedium2x($value)
 * @method static Builder|Photo whereModel($value)
 * @method static Builder|Photo whereOwnerId($value)
 * @method static Builder|Photo wherePublic($value)
 * @method static Builder|Photo whereShutter($value)
 * @method static Builder|Photo whereSize($value)
 * @method static Builder|Photo whereSmall($value)
 * @method static Builder|Photo whereSmall2x($value)
 * @method static Builder|Photo whereStar($value)
 * @method static Builder|Photo whereTags($value)
 * @method static Builder|Photo whereTakestamp($value)
 * @method static Builder|Photo whereThumb2x($value)
 * @method static Builder|Photo whereThumbUrl($value)
 * @method static Builder|Photo whereTitle($value)
 * @method static Builder|Photo whereType($value)
 * @method static Builder|Photo whereUpdatedAt($value)
 * @method static Builder|Photo whereUrl($value)
 * @method static Builder|Photo whereWidth($value)
 * @mixin Eloquent
 */
class Photo extends Model
{

	/**
	 * This extends the date types from Model to allow coercion with Carbon object.
	 *
	 * @var array dates
	 */
	protected $dates = [
		'created_at',
		'updated_at',
		'takestamp'
	];


	protected $casts = [
		'public' => 'int',
		'star'  => 'int',
		'downloadable'  => 'int'
	];


	/**
	 * Return the relationship between a Photo and its Album
	 *
	 * @return BelongsTo
	 */
	public function album()
	{
		return $this->belongsTo('App\Album', 'album_id', 'id')->withDefault(['public' => '1']);
	}



	/**
	 * Return the relationship between a Photo and its Owner
	 *
	 * @return BelongsTo
	 */
	public function owner()
	{
		return $this->belongsTo('App\User', 'owner_id', 'id')->withDefault([
			'id'       => 0,
			'username' => 'Admin'
		]);
	}



	/**
	 * Check if a photo already exists in the database via its checksum
	 *
	 * @param string $checksum
	 * @param $photoID
	 * @return Photo|bool|Builder|Model|object
	 */
	public function isDuplicate(string $checksum, $photoID = null)
	{
		$sql = $this->where('checksum', '=', $checksum);
		if (isset($photoID)) {
			$sql = $sql->where('id', '<>', $photoID);
		}

		return ($sql->count() == 0) ? false : $sql->first();
	}



	/**
	 * Returns photo-attributes into a front-end friendly format. Note that some attributes remain unchanged.
	 *
	 * @return array Returns photo-attributes in a normalized structure.
	 */
	public function prepareData()
	{

		// Init
		$photo = array();

		// Set unchanged attributes
		$photo['id'] = $this->id;
		$photo['title'] = $this->title;
		$photo['tags'] = $this->tags;
		$photo['star'] = $this->star == 1 ? '1' : '0';
		$photo['album'] = $this->album_id;
		$photo['width'] = $this->width;
		$photo['height'] = $this->height;
		$photo['type'] = $this->type;
		$photo['size'] = $this->size;
		$photo['iso'] = $this->iso;
		$photo['aperture'] = $this->aperture;
		$photo['make'] = $this->make;
		$photo['model'] = $this->model;
		$photo['shutter'] = $this->shutter;
		$photo['focal'] = $this->focal;
		$photo['lens'] = $this->lens;
		$photo['latitude'] = $this->latitude;
		$photo['longitude'] = $this->longitude;
		$photo['altitude'] = $this->altitude;
		$photo['sysdate'] = $this->created_at->format('d F Y');
		$photo['tags'] = $this->tags;
		$photo['description'] = $this->description == null ? '' : $this->description;
		$photo['license'] = Configs::get_value('default_license'); // default

		// shutter speed needs to be processed. It is stored as a string `a/b s`
		if ($photo['shutter'] != '' && substr($photo['shutter'], 0, 2) != '1/') {

			preg_match('/(\d+)\/(\d+) s/', $photo['shutter'], $matches);
			if ($matches) {
				$a = intval($matches[1]);
				$b = intval($matches[2]);
				$gcd = Helpers::gcd($a, $b);
				$a = $a / $gcd;
				$b = $b / $gcd;
				if ($a == 1) {
					$photo['shutter'] = '1/'.$b.' s';
				}
				else {
					$photo['shutter'] = ($a / $b).' s';
				}
			}
		}

		if ($photo['shutter'] == '1/1 s') {
			$photo['shutter'] = '1 s';
		}


		// check if license is none
		if ($this->license == 'none') {

			// check if it has an album
			if ($this->album_id != 0) {
				// this does not include sub albums setting. Do we want this ?
				// this will need to be changed if we want to add license backtracking
				$l = $this->album->license;
				if ($l != 'none') {
					$photo['license'] = $l;
				}
			}
		}
		else {
			$photo['license'] = $this->license;
		}

		// if this is a video
		if (strpos($this->type, 'video') === 0) {
			$photoUrl = $this->thumbUrl;
		}
		else {
			$photoUrl = $this->url;
		}
		$photoUrl2x = explode('.', $photoUrl);
		$photoUrl2x = $photoUrl2x[0].'@2x.'.$photoUrl2x[1];

		// Parse medium
		if ($this->medium != '') {
			$photo['medium'] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_MEDIUM').$photoUrl;
			$photo['medium_dim'] = $this->medium;
		}
		else {
			$photo['medium'] = '';
			$photo['medium_dim'] = '';
		}

		if ($this->medium2x != '') {
			$photo['medium2x'] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_MEDIUM').$photoUrl2x;
			$photo['medium2x_dim'] = $this->medium2x;
		}
		else {
			$photo['medium2x'] = '';
			$photo['medium2x_dim'] = '';
		}

		if ($this->small != '') {
			$photo['small'] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_SMALL').$photoUrl;
			$photo['small_dim'] = $this->small;
		}
		else {
			$photo['small'] = '';
			$photo['small_dim'] = '';
		}

		if ($this->small2x != '') {
			$photo['small2x'] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_SMALL').$photoUrl2x;
			$photo['small2x_dim'] = $this->small2x;
		}
		else {
			$photo['small2x'] = '';
			$photo['small2x_dim'] = '';
		}

		// Parse paths
		$photo['thumbUrl'] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_THUMB').$this->thumbUrl;

		if ($this->thumb2x == '1') {
			$thumbUrl2x = explode(".", $this->thumbUrl);
			$thumbUrl2x = $thumbUrl2x[0].'@2x.'.$thumbUrl2x[1];
			$photo['thumb2x'] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_THUMB').$thumbUrl2x;
		}
		else {
			$photo['thumb2x'] = '';
		}

		$photo['url'] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_BIG').$this->url;

		// Use takestamp as sysdate when possible
		if (isset($this->takestamp) && $this->takestamp != null) {

			// Use takestamp
			$photo['cameraDate'] = '1';
			$photo['sysdate'] = $this->created_at->format('d F Y');
			$photo['takedate'] = $this->takestamp->format('d F Y \a\t H:i');

		}
		else {

			// Use sysstamp from the id
			$photo['cameraDate'] = '0';
			$photo['sysdate'] = $this->created_at->format('d F Y');
			$photo['takedate'] = '';

		}

		$photo['public'] = $this->public == 1 ? '1' : '0';

		if ($this->album_id != null) {
			$photo['public'] = $this->album->public == '1' ? '2' : $photo['public'];
		}

		return $photo;

	}



	/**
	 * Before calling the delete() method which will remove the entry from the database, we need to remove the files.
	 *
	 * @return bool
	 */
	public function predelete()
	{

		if ($this->isDuplicate($this->checksum, $this->id)) {
			Logs::notice(__METHOD__, __LINE__, $this->id.' is a duplicate!');
			// it is a duplicate, we do not delete!
			return true;
		}

		$error = false;
		// quick check...
		if (!file_exists(Config::get('defines.dirs.LYCHEE_UPLOADS_BIG').$this->url)) {
			Logs::error(__METHOD__, __LINE__, 'Could not find picture in '.Config::get('defines.dirs.LYCHEE_UPLOADS_BIG'));
			$error = true;
		}

		// Delete big
		if (file_exists(Config::get('defines.dirs.LYCHEE_UPLOADS_BIG').$this->url) && !unlink(Config::get('defines.dirs.LYCHEE_UPLOADS_BIG').$this->url)) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete photo in uploads/big/');
			$error = true;
		}

		if (strpos($this->type, 'video') === 0) {
			$photoName = $this->thumbUrl;
		}
		else {
			$photoName = $this->url;
		}
		$photoName2x = explode('.', $photoName);
		$photoName2x = $photoName2x[0].'@2x.'.$photoName2x[1];

		// Delete medium
		if (file_exists(Config::get('defines.dirs.LYCHEE_UPLOADS_MEDIUM').$photoName) && !unlink(Config::get('defines.dirs.LYCHEE_UPLOADS_MEDIUM').$photoName)) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete photo in uploads/medium/');
			$error = true;
		}

		if (file_exists(Config::get('defines.dirs.LYCHEE_UPLOADS_MEDIUM').$photoName2x) && !unlink(Config::get('defines.dirs.LYCHEE_UPLOADS_MEDIUM').$photoName2x)) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete high-res photo in uploads/medium/');
			$error = true;
		}

		// Delete small
		if (file_exists(Config::get('defines.dirs.LYCHEE_UPLOADS_SMALL').$photoName) && !unlink(Config::get('defines.dirs.LYCHEE_UPLOADS_SMALL').$photoName)) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete photo in uploads/small/');
			$error = true;
		}

		if (file_exists(Config::get('defines.dirs.LYCHEE_UPLOADS_SMALL').$photoName2x) && !unlink(Config::get('defines.dirs.LYCHEE_UPLOADS_SMALL').$photoName2x)) {
			Logs::error(__METHOD__, __LINE__, 'Could not delete high-res photo in uploads/small/');
			$error = true;
		}

		if ($this->thumbUrl != '') {
			// Get retina thumb url
			$thumbUrl2x = explode(".", $this->thumbUrl);
			$thumbUrl2x = $thumbUrl2x[0].'@2x.'.$thumbUrl2x[1];
			// Delete thumb
			if (file_exists(Config::get('defines.dirs.LYCHEE_UPLOADS_THUMB').$this->thumbUrl) && !unlink(Config::get('defines.dirs.LYCHEE_UPLOADS_THUMB').$this->thumbUrl)) {
				Logs::error(__METHOD__, __LINE__, 'Could not delete photo in uploads/thumb/');
				$error = true;
			}

			// Delete thumb@2x
			if (file_exists(Config::get('defines.dirs.LYCHEE_UPLOADS_THUMB').$thumbUrl2x) && !unlink(Config::get('defines.dirs.LYCHEE_UPLOADS_THUMB').$thumbUrl2x)) {
				Logs::error(__METHOD__, __LINE__, 'Could not delete high-res photo in uploads/thumb/');
				$error = true;
			}
		}


		return !$error;

	}


	/**
	 *  Defines a bunch of helpers
	 */

	/**
	 * @param $query
	 * @return mixed
	 */
	static public function set_order(Builder $query)
	{
		return $query->orderBy(Configs::get_value('sortingPhotos_col'), Configs::get_value('sortingPhotos_order'))
			->orderBy('photos.id', 'ASC');
	}



	/**
	 * @param $query
	 * @return mixed
	 */
	static public function select_stars(Builder $query)
	{
		return self::set_order($query->where('star', '=', 1));
	}



	/**
	 * @param $query
	 * @return mixed
	 */
	static public function select_public(Builder $query)
	{
		return self::set_order($query->where('public', '=', 1));
	}



	/**
	 * @param $query
	 * @return mixed
	 */
	static public function select_recent(Builder $query)
	{
		return self::set_order($query->where('created_at', '>=', Carbon::now()->subDays(1)->toDateTimeString()));
	}



	/**
	 * @param $query
	 * @return mixed
	 */
	static public function select_unsorted(Builder $query)
	{
		return self::set_order($query->where('album_id', '=', null));
	}




	/**
	 * Define scopes which we can directly use e.g. Photo::stars()->all()
	 *
	 */

	/**
	 * @param $query
	 * @return mixed
	 */
	public function scopeStars($query)
	{
		return self::select_stars($query);
	}



	/**
	 * @param $query
	 * @return mixed
	 */
	public function scopePublic($query)
	{
		return self::select_public($query);
	}



	/**
	 * @param $query
	 * @return mixed
	 */
	public function scopeRecent($query)
	{
		return self::select_recent($query);
	}



	/**
	 * @param $query
	 * @return mixed
	 */
	public function scopeUnsorted($query)
	{
		return self::select_unsorted($query);
	}



	/**
	 * @param $query
	 * @param $id
	 * @return mixed
	 */
	public function scopeOwnedBy(Builder $query, $id)
	{
		return $id == 0 ? $query : $query->where('id', '=', $id);
	}

}
