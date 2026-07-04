<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

abstract class BaseIncidentMail extends \App\Mail\Layouts\EmailLayout
{
    protected Incident $incident;
    protected ?User $actor;
    protected ?string $actionUrl;
    protected array $extraFields = [];

    public function __construct(Incident $incident, ?User $actor = null, ?string $actionUrl = null)
    {
        $this->incident = $incident;
        $this->actor = $actor;
        $this->actionUrl = $actionUrl ?? route('incidents.show', $incident);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubjectPrefix() . ' ' . $this->getSubject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->getView(),
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'actionUrl' => $this->actionUrl,
                'extraFields' => $this->extraFields,
                'greeting' => $this->getGreeting(),
                'body' => $this->getBody(),
            ]),
        );
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'type' => $this->notificationType,
        ]);
    }

    abstract protected function getSubject(): string;
    abstract protected function getView(): string;
    abstract protected function getGreeting(): string;
    abstract protected function getBody(): string;
}