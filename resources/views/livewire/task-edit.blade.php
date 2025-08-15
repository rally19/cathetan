<?php
use App\Models\Task;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('Edit Task')]
class extends Component {
    public Task $task;
    public string $title = '';
    public string $content = '';
    public string $priority = 'medium';

    public function mount(): void
    {
        $this->task = Task::find(request()->route('id'));
        $this->title = $this->task->title;
        $this->content = $this->task->content;
        $this->priority = $this->task->priority;
    }

    public function updateTask(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'in:high,medium,low'],
        ]);

        $this->task->update($validated);

        Flux::toast(
            variant: 'success',
            heading: 'Task Updated',
            text: 'Task has been successfully updated.',
            duration: 4000,
        );

        $this->redirectRoute('tasks', navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Edit Task</flux:heading>
        </div>
        <div>
            <flux:button :href="route('tasks')" wire:navigate>Back to Tasks</flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <div class="space-y-4 outline rounded-lg p-6">
            <flux:heading size="lg">Task Details</flux:heading>
            
            <form wire:submit="updateTask" class="space-y-4">
                <flux:input
                    wire:model="title"
                    label="Title"
                    placeholder="Enter task title"
                    required
                />
                
                <flux:textarea
                    wire:model="content"
                    label="Content"
                    placeholder="Enter task content"
                    rows="5"
                    required
                />

                <flux:select
                    wire:model="priority"
                    label="Priority"
                    required >
                    <option value="">-- Select Priority --</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </flux:select>

                <div class="pt-4">
                    <flux:button type="submit" variant="primary" class="w-full">
                        Update Task
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>