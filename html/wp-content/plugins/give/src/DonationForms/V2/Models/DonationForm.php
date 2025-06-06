<?php

namespace Give\DonationForms\V2\Models;

use DateTime;
use Give\DonationForms\Models\DonationForm as ModelsDonationForm;
use Give\DonationForms\Properties\GoalSettings;
use Give\DonationForms\V2\Actions\ConvertV2FormToV3Form;
use Give\DonationForms\V2\DataTransferObjects\DonationFormQueryData;
use Give\DonationForms\V2\Properties\DonationFormLevel;
use Give\DonationForms\V2\ValueObjects\DonationFormStatus;
use Give\Framework\Models\Contracts\ModelReadOnly;
use Give\Framework\Models\Model;
use Give\Framework\Models\ModelQueryBuilder;
use Give\Framework\Models\ValueObjects\Relationship;
use Give\Framework\Support\ValueObjects\Money;

/**
 * Class DonationForm
 *
 * @since 2.24.0
 *
 * @property int $id
 * @property string $title
 * @property DonationFormLevel[] $levels
 * @property bool $goalOption
 * @property int $totalNumberOfDonations
 * @property Money $totalAmountDonated
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 * @property DonationFormStatus $status
 * @since 4.3.0
 * @property GoalSettings $goalSettings
 * @property bool $usesFormBuilder
 * @property int $campaignId
 */
class DonationForm extends Model implements ModelReadOnly
{
    /**
     * @inheritdoc
     */
    protected $properties = [
        'id' => 'int',
        'title' => 'string',
        'levels' => 'array',
        'goalOption' => 'bool',
        'totalNumberOfDonations' => 'int',
        'totalAmountDonated' => Money::class,
        'createdAt' => DateTime::class,
        'updatedAt' => DateTime::class,
        'status' => DonationFormStatus::class,
        'goalSettings' => GoalSettings::class,
        'usesFormBuilder' => 'bool',
        'campaignId' => 'int',
    ];

    /**
     * @inheritdoc
     */
    protected $relationships = [
        'donations' => Relationship::HAS_MANY,
    ];

    /**
     * @since 2.24.0
     *
     * @param $id
     *
     * @return DonationForm|null
     */
    public static function find($id)
    {
        return give()->donationForms->getById($id);
    }

    /**
     * @since 2.24.0
     *
     * @return ModelQueryBuilder<DonationForm>
     */
    public static function query(): ModelQueryBuilder
    {
        return give()->donationForms->prepareQuery();
    }

    /**
     * @since 2.24.0
     *
     * @param object $object
     *
     * @return DonationForm
     */
    public static function fromQueryBuilderObject($object): DonationForm
    {
        return DonationFormQueryData::fromObject($object)->toDonationForm();
    }

    /**
     * @since 4.2.0
     */
    public function toV3Form(): ModelsDonationForm
    {
        return (new ConvertV2FormToV3Form($this))();
    }
}
