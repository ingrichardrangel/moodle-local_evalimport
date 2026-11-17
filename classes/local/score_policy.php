<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Resolve the scoring rules for an import.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\local;

/**
 * Resolves site defaults and authorised per-import settings.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class score_policy {
    /** @var string Main switch for the optional scoring rules. */
    public const MASTER = 'validatescores';

    /** @var string[] Optional rules governed by the main switch. */
    public const RULES = ['matchactivitymaximum', 'requireuniquescores', 'requirezerolevel'];

    /**
     * Read the preset for the import form.
     *
     * @param \stdClass|null $config Plugin configuration, if already loaded.
     * @return array
     */
    public static function defaults(?\stdClass $config = null): array {
        $config = $config ?? get_config('local_evalimport');
        $defaults = [];
        foreach (array_merge([self::MASTER], self::RULES) as $key) {
            $defaults[$key] = (bool)($config->$key ?? true);
        }
        return $defaults;
    }

    /**
     * Check if the user may change settings for an import in this course.
     *
     * @param \context_course $context Course context.
     * @param \stdClass|null $config Plugin configuration, if already loaded.
     * @return bool
     */
    public static function can_override(\context_course $context, ?\stdClass $config = null): bool {
        $config = $config ?? get_config('local_evalimport');
        return is_siteadmin() || (!empty($config->allowvalidationoverride) &&
            has_capability('local/evalimport:overridevalidation', $context));
    }

    /**
     * Resolve the effective policy and permissions at the time of import.
     *
     * @param \context_course $context Course context.
     * @param array|null $overrides Settings submitted by an authorised user.
     * @return array
     */
    public static function resolve(\context_course $context, ?array $overrides): array {
        $config = get_config('local_evalimport');
        $canoverride = self::can_override($context, $config);
        if ($overrides !== null && !$canoverride) {
            throw new \moodle_exception('previewsettingschanged', 'local_evalimport');
        }
        return [
            'canoverride' => $canoverride,
            'rules' => self::effective($config, $overrides),
        ];
    }

    /**
     * Resolve the rules that actually affect scoring. A legacy zero minimum
     * is now represented by requirezerolevel, with its own site default.
     *
     * @param \stdClass $config Plugin configuration.
     * @param array|null $overrides Authorised settings.
     * @return array
     */
    public static function effective(\stdClass $config, ?array $overrides = null): array {
        $settings = self::defaults($config);
        foreach ($settings as $key => $default) {
            $settings[$key] = (bool)($overrides[$key] ?? $default);
        }
        foreach (self::RULES as $key) {
            $settings[$key] = $settings[self::MASTER] && $settings[$key];
        }
        $minimum = (float)($config->minlevelscore ?? 0);
        $settings['minimumlevelscore'] = !empty($config->enableminlevelscore) && $minimum !== 0.0 ? $minimum : null;
        $settings['maximumlevelscore'] = !empty($config->enablemaxlevelscore) ? (float)($config->maxlevelscore ?? 10) : null;
        return $settings;
    }
}
