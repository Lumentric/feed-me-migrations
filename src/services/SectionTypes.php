<?php

namespace boldminded\craftfeedmemigrations\services;

use Craft;

class SectionTypes
{
    public function getDictionary(): array
    {
        $allSections = Craft::$app->getSections()->getAllSections();

        return array_combine(
            array_values(array_column($allSections, 'uid')),
            array_map(function ($section) {
                $entryTypes = $section->getEntryTypes();
                $entryTypesArray = [];

                foreach ($entryTypes as $type) {
                    $entryTypesArray[$type->uid] = [
                        'id' => $type->id,
                        'handle' => $type->handle,
                    ];
                }

                return [
                    'id' => $section->id,
                    'handle' => $section->handle,
                    'types' => $entryTypesArray,
                ];
            }, $allSections)
        );
    }

    public function getSectionInfoById(int $sectionId, int $sectionTypeId = 0): array
    {
        $dictionary = $this->getDictionary();

        $section = array_filter($dictionary, function ($data, $name) use ($sectionId) {
            return $data['id'] == $sectionId;
        }, ARRAY_FILTER_USE_BOTH);

        $sectionUID = key($section);

        if (!$sectionTypeId) {
            // @todo handle error
        }

        $sectionTypeUID = '';

        foreach ($dictionary[$sectionUID]['types'] as $sectionTypeUID => $typeValues) {
            if ($typeValues['id'] === $sectionTypeId) {
                break;
            }
        }

        if (!$sectionTypeUID) {
            // @todo handle error
        }

        return [
            $sectionUID,
            $sectionTypeUID
        ];
    }
}
