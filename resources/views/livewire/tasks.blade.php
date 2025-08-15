<?php
use Livewire\Attributes\{Layout, Title, Computed, Url};
use Livewire\Volt\Component;
use App\Models\Task;
use App\Models\User;

new #[Layout('components.layouts.app')] 
#[Title('Tasks')]
class extends Component {
    
    public bool $showFilters = false;
    public bool $loadingMore = false;
    public ?Task $taskToDelete = null;
    public int $perLoad = 10;
    public int $loadedCount = 0;

    #[Url(history: true)]
    public $filters = [
        'title' => '',
        'content' => '',
        'priority' => '',
        'checked_filter' => '',
    ];
    
    #[Url(history: true)]
    public $sortBy = 'created_at';

    #[Url(history: true)]
    public $sortDirection = 'desc';
    
    public function mount()
    {
        $sessionFilters = session()->get('tasks.filters', []);
        
        if (isset($sessionFilters['checked_only'])) {
            $sessionFilters['checked_filter'] = $sessionFilters['checked_only'] ? 'unchecked' : 'all';
            unset($sessionFilters['checked_only']);
        }
        
        $this->filters = array_merge([
            'title' => '',
            'content' => '',
            'priority' => '',
            'checked_filter' => 'all',
        ], $sessionFilters);
        
        $this->sortBy = session()->get('tasks.sortBy', $this->sortBy);
        $this->sortDirection = session()->get('tasks.sortDirection', $this->sortDirection);
        $this->loadedCount = session()->get('tasks.loadedCount', $this->perLoad);
    }
    
    public function updatedFilters()
    {
        session()->put('tasks.filters', $this->filters);
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('tasks.loadedCount', $this->loadedCount);
    }
    
    public function resetFilters()
    {
        $this->reset('filters');
        session()->forget('tasks.filters');
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('tasks.loadedCount', $this->loadedCount);
    }
    
    public function loadMore()
    {
        $this->loadingMore = true;
        $this->loadedCount += $this->perLoad;
        session()->put('tasks.loadedCount', $this->loadedCount);
        $this->loadingMore = false;
    }
    
    public function toggleTaskChecked($taskId)
    {
        $task = Task::find($taskId);
        if ($task) {
            $task->checked = $task->checked === 'true' ? 'false' : 'true';
            $task->save();
        }
    }
    
    public function confirmDelete($taskId)
    {
        $this->taskToDelete = Task::find($taskId);
        Flux::modal('confirm-delete')->show();
    }

    public function deleteTask()
    {
        if (!$this->taskToDelete) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Task not found',
            );
            return;
        }
        
        try {
            $this->taskToDelete->delete();
            
            Flux::toast(
                variant: 'success',
                heading: 'Task Deleted',
                text: 'Task successfully deleted',
            );
            
            $this->taskToDelete = null;
            Flux::modal('confirm-delete')->close();
            
            $this->reset('loadedCount');
            $this->loadedCount = $this->perLoad;
            session()->put('tasks.loadedCount', $this->loadedCount);
            
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete task: ' . $e->getMessage(),
            );
        }
    }
    
    #[Computed]
    public function getTasks()
    {
        return Task::query()
            ->with(['user'])
            ->where('users_id', auth()->id())
            ->when($this->filters['title'], function ($query) {
                $query->where('title', 'like', '%'.$this->filters['title'].'%');
            })
            ->when($this->filters['content'], function ($query) {
                $query->where('content', 'like', '%'.$this->filters['content'].'%');
            })
            ->when($this->filters['priority'], function ($query) {
                $query->where('priority', $this->filters['priority']);
            })
            ->when($this->filters['checked_filter'] === 'checked', function ($query) {
                $query->where('checked', 'true');
            })
            ->when($this->filters['checked_filter'] === 'unchecked', function ($query) {
                $query->where('checked', 'false');
            })
            ->when($this->sortBy, function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->get()
            ->map(function ($task) {
                $task->is_checked = $task->checked === 'true';
                return $task;
            });
    }
    
    public function sort($column, $direction = null) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = $direction ?? 'asc';
        }
        
        session()->put('tasks.sortBy', $this->sortBy);
        session()->put('tasks.sortDirection', $this->sortDirection);
        
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('tasks.loadedCount', $this->loadedCount);
    }
}; ?>

<div>
    <div class="flex items-center justify-between my-6">
        <div><flux:heading size="xl">Tasks</flux:heading></div>
        <div class="flex items-center gap-4">
            <div>
                <flux:button 
                    icon="plus" 
                    variant="primary" 
                    :href="route('task.create')" 
                    wire:navigate
                >
                    Create Task
                </flux:button>
            </div>
            <div>
                <flux:modal.trigger name="sort">
                    <flux:button type="button">
                        <span><flux:icon.arrows-up-down/></span> Sort
                    </flux:button>
                </flux:modal.trigger>
            </div>
            <div>
                <flux:modal.trigger name="filters">
                    <flux:button type="button">
                        <span><flux:icon.funnel/></span> Filters
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>
    
    @if($this->getTasks()->count())
    <div class="grid grid-cols-1 gap-6">
        @foreach ($this->getTasks()->take($this->loadedCount) as $task)
            <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow mb-4 last:mb-0">
                <div class="grid grid-cols-1 lg:grid-cols-20 gap-4 p-4">
                    
                    <div class="lg:col-span-1 flex flex-col items-start space-y-4">
                        <div class="flex items-center space-x-4">
                            <flux:checkbox 
                                :checked="$task->is_checked" 
                                wire:click="toggleTaskChecked({{ $task->id }})"
                                class="h-5 w-5"
                            />
                        </div>
                        <div class="flex items-center space-x-4">
                            <flux:button 
                                icon="pencil" 
                                variant="ghost" 
                                :href="route('task.edit', ['id' => $task->id])" 
                                wire:navigate
                            ></flux:button>
                        </div>
                        <div class="flex items-center space-x-4">
                            <flux:button 
                                icon="trash" 
                                variant="danger" 
                                wire:click="confirmDelete({{ $task->id }})"
                            ></flux:button>
                        </div>
                    </div>
                    
                    <div class="lg:col-span-19">
                        <div class="flex flex-col h-full justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:heading 
                                        size="lg"
                                        class="{{ $task->is_checked ? 'line-through text-gray-400' : '' }}"
                                    >
                                        {{ $task->title }}
                                    </flux:heading>
                                    @if($task->priority)
                                        <flux:badge :color="$task->priority === 'high' ? 'red' : ($task->priority === 'medium' ? 'yellow' : 'gray')">
                                            {{ ucfirst($task->priority) }} priority
                                        </flux:badge>
                                    @endif
                                </div>
                                
                                <div class="text-gray-600 dark:text-gray-400 mb-4">
                                    {{ Str::limit($task->content, 100, '...' ) }}
                                </div>
                            </div>
                            
                            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                Created on {{ $task->created_at->format('Y-m-d H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        
        @if($this->loadedCount < $this->getTasks()->count())
        <div class="flex justify-center mt-6" wire:loading.remove>
            <flux:button wire:click="loadMore" :loading="$loadingMore">
                Load More
            </flux:button>
        </div>
        @endif
        
        <div wire:loading>
            <div class="flex justify-center py-8">
                <flux:icon.loading />
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <div class="text-gray-400 mb-4">
            <flux:icon.magnifying-glass class="w-12 h-12 mx-auto" />
        </div>
        <p class="text-neutral-600 dark:text-neutral-400 text-lg">No tasks found matching your criteria.</p>
        <div class="mt-4">
            <flux:button wire:click="resetFilters">Reset Filters</flux:button>
        </div>
    </div>
    @endif
    
    <flux:modal name="sort" class="w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Sort By</flux:heading>
            </div>
            
            <div class="space-y-4">
                <div>
                    <flux:button 
                        type="button" 
                        variant="{{ $sortBy === 'created_at' ? 'primary' : 'ghost' }}" 
                        wire:click="sort('created_at')"
                        class="w-full"
                    >
                        <div class="inline-flex items-center">Created Date
                        @if($sortBy === 'created_at')
                            <flux:icon.arrow-down class="ml-2 w-4 h-4 {{ $sortDirection === 'desc' ? 'transform rotate-180' : '' }}" />
                        @endif
                        </div>
                    </flux:button>
                </div>
                
                <div>
                    <flux:button 
                        type="button" 
                        variant="{{ $sortBy === 'title' ? 'primary' : 'ghost' }}" 
                        wire:click="sort('title')"
                        class="w-full inline-flex items-center"
                    >
                        <div class="inline-flex items-center">Title
                        @if($sortBy === 'title')
                            <flux:icon.arrow-down class="ml-2 w-4 h-4 {{ $sortDirection === 'desc' ? 'transform rotate-180' : '' }}" />
                        @endif
                        </div>
                    </flux:button>
                </div>
                
                <div>
                    <flux:button 
                        type="button" 
                        variant="{{ $sortBy === 'priority' ? 'primary' : 'ghost' }}" 
                        wire:click="sort('priority')"
                        class="w-full inline-flex items-center"
                    >
                        <div class="inline-flex items-center">Priority
                        @if($sortBy === 'priority')
                            <flux:icon.arrow-down class="ml-2 w-4 h-4 {{ $sortDirection === 'desc' ? 'transform rotate-180' : '' }}" />
                        @endif
                        </div>
                    </flux:button>
                </div>
            </div>
            
            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
    
    <flux:modal name="filters" class="w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Filters</flux:heading>
            </div>
            
            <div>
                <flux:label>Title</flux:label>
                <flux:input wire:model.live="filters.title" placeholder="Search by title..." />
            </div>
            
            <div>
                <flux:label>Content</flux:label>
                <flux:input wire:model.live="filters.content" placeholder="Search by content..." />
            </div>
            
            <div>
                <flux:label>Priority</flux:label>
                <flux:select variant="combobox" wire:model.live="filters.priority">
                    <flux:select.option value="">All Priorities</flux:select.option>
                    <flux:select.option value="high">High</flux:select.option>
                    <flux:select.option value="medium">Medium</flux:select.option>
                    <flux:select.option value="low">Low</flux:select.option>
                </flux:select>
            </div>
            
            <div>
                <flux:label>Task Status</flux:label>
                <flux:select variant="combobox" wire:model.live="filters.checked_filter">
                    <flux:select.option value="all">Show All Tasks</flux:select.option>
                    <flux:select.option value="checked">Only Checked Tasks</flux:select.option>
                    <flux:select.option value="unchecked">Only Unchecked Tasks</flux:select.option>
                </flux:select>
            </div>

            
            <div class="flex">
                <flux:button type="button" wire:click="resetFilters">
                    Reset Filters
                </flux:button>
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="confirm-delete" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete task?</flux:heading>
                <flux:text class="mt-2">
                    @if($this->taskToDelete)
                        <p>You're about to delete <strong>{{ $this->taskToDelete->title }}</strong>.</p>
                        <p>This action cannot be undone.</p>
                    @endif
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="danger" 
                    wire:click="deleteTask"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Delete Task</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>