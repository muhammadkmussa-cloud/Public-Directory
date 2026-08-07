<?php
/**
 * Ummah Directory — Server-Side Pre-Renderer Helper
 * Fetches entity metadata for pre-rendering OpenGraph, Twitter, and Schema.org JSON-LD tags.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

class PreRenderer
{
    /**
     * Fetch entity metadata from the database.
     *
     * @param string $type Entity type ('business', 'mosque', 'fundi', 'charity')
     * @param int $id Entity ID
     * @return array|null Metadata array or null if not found
     */
    /**
     * Fetch entity metadata from the database or map from pre-fetched array.
     *
     * @param string $type Entity type ('business', 'mosque', 'fundi', 'charity')
     * @param int $id Entity ID
     * @param array|null $entity Optional pre-fetched entity row
     * @return array|null Metadata array or null if not found
     */
    public static function fetchMeta($type, $id, $entity = null)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        $db = Database::getInstance();

        switch ($type) {
            case 'business':
                $row = $entity ?: $db->fetchOne(
                    "SELECT b.id, b.name, b.description, b.short_description, b.city, b.address, b.latitude, b.longitude,
                            b.rating_average AS rating, b.review_count,
                            (SELECT c.name FROM categories c 
                             JOIN business_categories bc ON bc.category_id = c.id 
                             WHERE bc.business_id = b.id LIMIT 1) AS category_name,
                            (SELECT photo_path FROM business_photos 
                             WHERE business_id = b.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_photo
                     FROM businesses b
                     WHERE b.id = ? AND b.is_open = 1",
                    [$id]
                );
                if (!$row) return null;
                $desc = $row['short_description'] ?? ($row['description'] ?? '');
                return [
                    'id'           => $row['id'],
                    'type'         => 'business',
                    'name'         => $row['name'],
                    'description'  => $desc ?: ($row['name'] . ' in ' . ($row['city'] ?? '')),
                    'city'         => $row['city'] ?? '',
                    'address'      => $row['address'] ?? '',
                    'image'        => $row['primary_photo'] ?? ($row['image'] ?? 'assets/img/sample/restaurant-1.jpg'),
                    'rating'       => (float)($row['rating'] ?? ($row['rating_average'] ?? 0)),
                    'review_count' => (int)($row['review_count'] ?? 0),
                    'category'     => $row['category_name'] ?? 'Business',
                    'latitude'     => $row['latitude'] ?? null,
                    'longitude'    => $row['longitude'] ?? null,
                ];

            case 'mosque':
                $row = $entity ?: $db->fetchOne(
                    "SELECT m.id, m.name, m.description, m.city, m.address, m.latitude, m.longitude,
                            m.rating_average AS rating, m.review_count,
                            (SELECT photo_path FROM mosque_photos 
                             WHERE mosque_id = m.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_photo
                     FROM mosques m
                     WHERE m.id = ?",
                    [$id]
                );
                if (!$row) return null;
                return [
                    'id'           => $row['id'],
                    'type'         => 'mosque',
                    'name'         => $row['name'],
                    'description'  => ($row['description'] ?? '') ?: ($row['name'] . ' in ' . ($row['city'] ?? '')),
                    'city'         => $row['city'] ?? '',
                    'address'      => $row['address'] ?? '',
                    'image'        => $row['primary_photo'] ?? ($row['image'] ?? 'assets/img/sample/mosque-1.jpg'),
                    'rating'       => (float)($row['rating'] ?? ($row['rating_average'] ?? 0)),
                    'review_count' => (int)($row['review_count'] ?? 0),
                    'category'     => 'Mosque',
                    'latitude'     => $row['latitude'] ?? null,
                    'longitude'    => $row['longitude'] ?? null,
                ];

            case 'fundi':
                $row = $entity ?: $db->fetchOne(
                    "SELECT f.id, u.full_name AS name, f.bio AS description, f.profession, f.city, f.latitude, f.longitude,
                            f.rating_average AS rating, f.review_count, u.profile_photo AS primary_photo,
                            (SELECT c.name FROM categories c 
                             JOIN fundi_categories fc ON fc.category_id = c.id 
                             WHERE fc.fundi_id = f.id LIMIT 1) AS category_name
                     FROM fundis f
                     JOIN users u ON u.id = f.user_id
                     WHERE f.id = ?",
                    [$id]
                );
                if (!$row) return null;
                $name = $row['name'] ?? ($row['full_name'] ?? ('Fundi #' . $row['id']));
                $prof = $row['profession'] ?? 'Fundi';
                $desc = $row['description'] ?? ($row['bio'] ?? '');
                return [
                    'id'           => $row['id'],
                    'type'         => 'fundi',
                    'name'         => $name,
                    'description'  => $desc ?: ($name . ' — ' . $prof . ' in ' . ($row['city'] ?? '')),
                    'city'         => $row['city'] ?? '',
                    'address'      => $row['city'] ?? '',
                    'image'        => $row['primary_photo'] ?? ($row['profile_photo'] ?? 'assets/img/sample/fundi-1.jpg'),
                    'rating'       => (float)($row['rating'] ?? ($row['rating_average'] ?? 0)),
                    'review_count' => (int)($row['review_count'] ?? 0),
                    'category'     => $row['category_name'] ?? ($prof ?: 'Fundi'),
                    'latitude'     => $row['latitude'] ?? null,
                    'longitude'    => $row['longitude'] ?? null,
                ];

            case 'charity':
                $row = $entity ?: $db->fetchOne(
                    "SELECT c.id, c.name, c.description, c.city, c.address, c.logo_path AS primary_photo,
                            c.is_verified, c.rating_average AS rating
                     FROM charities c
                     WHERE c.id = ?",
                    [$id]
                );
                if (!$row) return null;
                return [
                    'id'           => $row['id'],
                    'type'         => 'charity',
                    'name'         => $row['name'],
                    'description'  => ($row['description'] ?? '') ?: ($row['name'] . ' — Non-profit organization'),
                    'city'         => $row['city'] ?? '',
                    'address'      => $row['address'] ?? ($row['city'] ?? ''),
                    'image'        => $row['primary_photo'] ?? ($row['logo_path'] ?? 'assets/img/sample/charity-1.jpg'),
                    'rating'       => (float)($row['rating'] ?? ($row['rating_average'] ?? 5.0)),
                    'review_count' => 0,
                    'category'     => 'Charity',
                    'latitude'     => null,
                    'longitude'    => null,
                ];

            default:
                return null;
        }
    }

    /**
     * Render HTML meta tags and JSON-LD structured data string.
     *
     * @param string $type Entity type ('business', 'mosque', 'fundi', 'charity')
     * @param int $id Entity ID
     * @param array|null $entity Optional pre-fetched entity row
     * @return string HTML tags string for head insertion
     */
    public static function renderHeaderTags($type, $id, $entity = null)
    {
        $meta = self::fetchMeta($type, $id, $entity);
        if (!$meta) {
            return '';
        }

        $appUrl = defined('APP_URL') && APP_URL ? rtrim(APP_URL, '/') : '';
        $imageUrl = $meta['image'];
        if (!preg_match('~^(https?:)?//|data:~i', $imageUrl)) {
            $imageUrl = ($appUrl ?: '') . '/' . ltrim($imageUrl, '/');
        }

        $pageUrl = ($appUrl ?: '') . '/' . $type . '.php?id=' . $meta['id'];

        $cleanDesc = preg_replace('/\s+/', ' ', trim($meta['description']));

        $title = htmlspecialchars($meta['name'] . ' — Ummah Directory', ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($cleanDesc, ENT_QUOTES, 'UTF-8');
        $ogTitle = htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8');
        $ogImage = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
        $ogUrl = htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8');

        // Schema.org JSON-LD mapping
        $schemaType = 'LocalBusiness';
        if ($type === 'mosque') $schemaType = 'PlaceOfWorship';
        elseif ($type === 'fundi') $schemaType = 'Person';
        elseif ($type === 'charity') $schemaType = 'NGO';

        $jsonLd = [
            '@context'    => 'https://schema.org',
            '@type'       => $schemaType,
            'name'        => $meta['name'],
            'description' => $cleanDesc,
            'image'       => $imageUrl,
            'url'         => $pageUrl,
        ];

        if (!empty($meta['address'])) {
            $jsonLd['address'] = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $meta['address'],
                'addressLocality' => $meta['city'],
            ];
        }
        if (!empty($meta['latitude']) && !empty($meta['longitude'])) {
            $jsonLd['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float)$meta['latitude'],
                'longitude' => (float)$meta['longitude'],
            ];
        }
        if ($meta['rating'] > 0 && $meta['review_count'] > 0) {
            $jsonLd['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $meta['rating'],
                'reviewCount' => $meta['review_count'],
            ];
        }

        $jsonLdFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                     | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $jsonLdString = json_encode($jsonLd, $jsonLdFlags);

        $html = [];
        $html[] = "<title>{$title}</title>";
        $html[] = "<meta name=\"description\" content=\"{$description}\">";
        $html[] = "<meta property=\"og:title\" content=\"{$ogTitle}\">";
        $html[] = "<meta property=\"og:description\" content=\"{$description}\">";
        $html[] = "<meta property=\"og:image\" content=\"{$ogImage}\">";
        $html[] = "<meta property=\"og:url\" content=\"{$ogUrl}\">";
        $html[] = "<meta property=\"og:type\" content=\"website\">";
        $html[] = "<meta name=\"twitter:card\" content=\"summary_large_image\">";
        $html[] = "<meta name=\"twitter:title\" content=\"{$ogTitle}\">";
        $html[] = "<meta name=\"twitter:description\" content=\"{$description}\">";
        $html[] = "<meta name=\"twitter:image\" content=\"{$ogImage}\">";
        $html[] = "<script type=\"application/ld+json\">\n" . $jsonLdString . "\n</script>";

        return implode("\n    ", $html);
    }
}
