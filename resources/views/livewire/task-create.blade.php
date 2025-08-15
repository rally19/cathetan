<?php
use App\Models\Task;
use App\Models\Tag;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('Create Task')]
class extends Component {
    public string $title = '';
    public string $content = '';
    public string $priority = 'medium';

    public function createTask(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'in:high,medium,low'],
        ]);

        $validated['users_id'] = auth()->id();

        Flux::toast(
            variant: 'success',
            heading: 'Task Created',
            text: 'Task has been successfully created.',
            duration: 4000,
        );

        Task::create($validated);

        $this->redirectRoute('tasks', navigate: true);

        $this->reset();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Create New Task</flux:heading>
        </div>
        <div>
            <flux:button :href="route('tasks')" wire:navigate>Back to Tasks</flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <div class="space-y-4 outline rounded-lg p-6">
            <flux:heading size="lg">Task Details</flux:heading>
            
            <form wire:submit="createTask" class="space-y-4">
                <flux:input
                    wire:model="title"
                    label="Title"
                    placeholder="Enter task title"
                    required
                />
                
                <flux:textarea
                    wire:model="content"
                    label="Conten"
                    placeholder="Enter task content"
                    rows="5"
                    required
                />

                <flux:select
                    wire:model="priority"
                    label="Priority"
                    required >
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </flux:select>

                <div class="pt-4">
                    <flux:button type="submit" variant="primary" class="w-full">
                        Create Task
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>