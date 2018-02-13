<?php

namespace SilverShop\Discounts\Extensions\Constraints;


use SilverShop\Discounts\Model\Discount;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;

class MembershipDiscountConstraint extends DiscountConstraint
{
    private static $many_many = [
        "Members" => Member::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->isInDB()) {
            $fields->addFieldToTab("Root.Main.Constraints.Members",
                GridField::create("Members", "Members",
                    $this->owner->Members(),
                    GridFieldConfig_RelationEditor::create()
                        ->removeComponentsByType("GridFieldAddNewButton")
                        ->removeComponentsByType("GridFieldEditButton")
                )
            );
        }
    }

    public function filter(DataList $list)
    {
        $memberid = 0;
        if ($member = $this->getMember()) {
            $memberid = $member->ID;
        }
        $list = $list->leftJoin(
            "Discount_Members",
            "\"Discount_Members\".\"DiscountID\" = \"Discount\".\"ID\""
        )->where("(\"Discount_Members\".\"MemberID\" IS NULL) OR \"Discount_Members\".\"MemberID\" = $memberid");

        return $list;
    }

    public function check(Discount $discount)
    {
        $members = $discount->Members();
        $member = $this->getMember();
        if ($members->exists() && (!$member || !$members->byID($member->ID))) {
            $this->error(_t(
                "Discount.MEMBERSHIP",
                "Only specific members can use this discount."
            ));
            return false;
        }

        return true;
    }

    public function getMember()
    {
        return isset($this->context['Member']) && is_object($this->context['Member']) ? $this->context['Member'] : $this->order->Member();
    }
}