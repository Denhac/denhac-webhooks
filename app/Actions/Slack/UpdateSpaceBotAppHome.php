<?php

namespace App\Actions\Slack;

use App\Customer;
use App\Printer3D;
use App\Slack\CommonResponses;
use App\Slack\SlackApi;
use App\UserMembership;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Surfaces\AppHome;
use Spatie\QueueableAction\QueueableAction;

class UpdateSpaceBotAppHome
{
    use QueueableAction;

    private SlackApi $slackApi;

    private AppHome $home;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
        $this->home = Kit::newAppHome();
    }

    /**
     * Execute the action.
     *
     * @param string $slack_id
     * @return void
     */
    public function execute(string $slack_id)
    {
        /** @var Customer $member */
        $member = Customer::whereSlackId($slack_id)->first();

        if(is_null($member)) {
            $this->home->text(CommonResponses::unrecognizedUser());
        } else if(! $member->member) {
            $this->home->text(CommonResponses::notAMemberInGoodStanding());
        } else {
            $this->activeMember($member);
        }

        $this->slackApi->views->publish($slack_id, $this->home);
    }

    private function activeMember(Customer $member)
    {
        $this->home->text("You're an active member! Thank you for being part of denhac!");

        if($member->hasMembership(UserMembership::MEMBERSHIP_3DP_USER)) {
            $this->equipment3DPrinterStatus();
        }

        if($member->hasMembership(UserMembership::MEMBERSHIP_3DP_TRAINER)) {
            $this->home->text("You are authorized to train others to use the 3d printers");
        }

        if($member->hasMembership(UserMembership::MEMBERSHIP_LASER_CUTTER_USER)) {
            $this->home->text("You are authorized to use the laser cutter");
        }

        if($member->hasMembership(UserMembership::MEMBERSHIP_LASER_CUTTER_TRAINER)) {
            $this->home->text("You are authorized to train others to use the laser cutter");
        }
    }

    private function equipment3DPrinterStatus()
    {
        $this->home->divider();
        $this->home->header("3D Printers");
        $printers = Printer3D::all();

        $printers->each(function($printer) {
            /** @var Printer3D $printer */
            if($printer->status == Printer3D::STATUS_PRINT_STARTED) {
                $message = ":printer: Printer is in use.";
            } else if($printer->status == Printer3D::STATUS_PRINT_DONE) {
                $message = ":large_green_circle: Printer is ready to go.";
            } else if($printer->status == Printer3D::STATUS_PRINT_FAILED) {
                $message = ":bangbang: The last print has failed. The printer may be available.";
            } else if($printer->status == Printer3D::STATUS_PRINT_PAUSED) {
                $message = ":double_vertical_bar: Printer is paused.";
            } else if($printer->status == Printer3D::STATUS_ERROR) {
                $message = ":bangbang: Something went wrong (eg printer disconnect). The printer may be available.";
            } else if($printer->status == Printer3D::STATUS_USER_ACTION_NEEDED) {
                $message = ":warning: User action needed.";
            } else {
                $message = ":question: The printer is in an unknown state.";
            }

            $this->home->text("$printer->name $message");
        });
    }
}
