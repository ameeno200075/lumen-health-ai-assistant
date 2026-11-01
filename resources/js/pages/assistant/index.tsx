import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    SendHorizontal,
    Plus,
    History,
    Stethoscope,
    MessageSquare,
    CalendarPlus,
    Search,
    CalendarCheck,
    Sparkles,
} from 'lucide-react';

interface ChatMessage {
    id: string | number;
    role: 'user' | 'assistant';
    content: string;
}

interface Conversation {
    id: string;
    title: string | null;
    updated_at: string;
}

interface AssistantPageProps {
    conversations: Conversation[];
}

function xsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Conversation dates use the clinic timezone, matching appointment display.
 */
function formatConversationDate(dateString: string): string {
    const date = new Date(dateString);

    if (Number.isNaN(date.getTime())) {
        return 'Unknown date';
    }

    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Damascus',
    }).format(date);
}

export default function AssistantPage({ conversations }: AssistantPageProps) {
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [conversationId, setConversationId] = useState<string | null>(null);
    const [showHistory, setShowHistory] = useState(false);

    const bottomRef = useRef<HTMLDivElement>(null);

    /*
     * Automatically scroll to the latest message.
     */
    useEffect(() => {
        bottomRef.current?.scrollIntoView({
            behavior: 'smooth',
            block: 'end',
        });
    }, [messages, loading]);

    /*
     * Send a message to the Laravel backend.
     */
    async function send(text: string) {
        const content = text.trim();

        if (!content || loading) {
            return;
        }

        // Add the user's message immediately to the UI.
        setMessages((previous) => [
            ...previous,
            {
                id: `temp-user-${Date.now()}`,
                role: 'user',
                content,
            },
        ]);

        setInput('');
        setLoading(true);

        try {
            const response = await fetch('/assistant/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({
                    message: content,
                    conversation_id: conversationId,
                }),
            });

            if (!response.ok) {
                throw new Error(
                    `Assistant request failed with status ${response.status}`,
                );
            }

            const data: {
                response: string;
                conversation_id: string | null;
            } = await response.json();

            // Save the conversation ID for subsequent messages.
            if (data.conversation_id) {
                setConversationId(data.conversation_id);
            }

            // Display the assistant's response.
            setMessages((previous) => [
                ...previous,
                {
                    id: `temp-assistant-${Date.now()}`,
                    role: 'assistant',
                    content:
                        data.response?.trim() ||
                        'I could not generate a response. Please try again.',
                },
            ]);
        } catch (error) {
            /*
             * Log technical details only during development.
             */
            if (import.meta.env.DEV) {
                console.error('Assistant request failed:', error);
            }

            setMessages((previous) => [
                ...previous,
                {
                    id: `error-${Date.now()}`,
                    role: 'assistant',
                    content:
                        'Sorry, I couldn’t process your message. Please try again in a moment.',
                },
            ]);
        } finally {
            setLoading(false);
        }
    }

    /*
     * Start a new conversation without deleting existing history.
     */
    function newChat() {
        if (loading) {
            return;
        }

        setMessages([]);
        setConversationId(null);
        setShowHistory(false);
        setInput('');
    }

    /*
     * Load an existing conversation from Laravel.
     */
    async function loadConversation(id: string) {
        if (loading) {
            return;
        }

        setShowHistory(false);
        setLoading(true);

        try {
            const response = await fetch(
                `/assistant/conversation/${encodeURIComponent(id)}`,
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `Failed to load conversation: ${response.status}`,
                );
            }

            const data: {
                conversation_id: string;
                messages: ChatMessage[];
            } = await response.json();

            // Exclude empty messages left by previous failed requests.
            const validMessages = data.messages.filter(
                (message) =>
                    (message.role === 'user' || message.role === 'assistant') &&
                    typeof message.content === 'string' &&
                    message.content.trim().length > 0,
            );

            setMessages(validMessages);
            setConversationId(data.conversation_id);
        } catch (error) {
            if (import.meta.env.DEV) {
                console.error('Failed to load conversation:', error);
            }

            setMessages([
                {
                    id: `load-error-${Date.now()}`,
                    role: 'assistant',
                    content:
                        'Sorry, I couldn’t load this conversation. Please try again.',
                },
            ]);
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="flex h-[calc(100dvh-4rem)] min-h-[600px] w-full flex-col bg-background">
            {/* Header */}
            <header className="flex items-center gap-3 border-b px-5 py-4">
                <div className="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Stethoscope className="size-5" />
                </div>

                <div>
                    <h1 className="font-semibold">Lumen Health assistant</h1>

                    <p className="text-xs text-muted-foreground">
                        Your intelligent clinic receptionist
                    </p>
                </div>

                <div className="ml-auto flex items-center gap-2 text-xs text-muted-foreground">
                    <span className="size-2 rounded-full bg-green-500" />
                    Assistant
                </div>
            </header>

            {/* Conversation toolbar */}
            <div className="flex items-center justify-between gap-2 border-b px-4 py-3 sm:px-6">
                <div className="min-w-0">
                    <p className="truncate text-sm font-medium">
                        {conversationId
                            ? conversations.find(
                                  (conversation) =>
                                      conversation.id === conversationId,
                              )?.title || 'Current conversation'
                            : 'New conversation'}
                    </p>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={newChat}
                        disabled={loading}
                    >
                        <Plus className="mr-1 size-4" />
                        New chat
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setShowHistory((previous) => !previous)}
                        disabled={loading || conversations.length === 0}
                        aria-expanded={showHistory}
                    >
                        <History className="mr-1 size-4" />
                        History
                    </Button>
                </div>
            </div>

            {/* Conversation history panel */}
            {showHistory && (
                <div className="border-b bg-muted/20 px-4 py-3 sm:px-6">
                    <div className="mx-auto max-w-3xl">
                        <h2 className="mb-2 text-sm font-semibold">
                            Previous conversations
                        </h2>

                        {conversations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No previous conversations yet.
                            </p>
                        ) : (
                            <div className="max-h-48 space-y-1 overflow-y-auto">
                                {conversations.map((conversation) => (
                                    <button
                                        key={conversation.id}
                                        type="button"
                                        onClick={() =>
                                            void loadConversation(
                                                conversation.id,
                                            )
                                        }
                                        className={`flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm transition hover:bg-muted ${
                                            conversation.id === conversationId
                                                ? 'bg-muted'
                                                : ''
                                        }`}
                                    >
                                        <span className="flex min-w-0 items-center gap-2">
                                            <MessageSquare className="size-4 shrink-0 text-muted-foreground" />

                                            <span className="truncate">
                                                {conversation.title ||
                                                    'Untitled conversation'}
                                            </span>
                                        </span>

                                        {/* Show both the date and the time. */}
                                        <time
                                            dateTime={conversation.updated_at}
                                            title={formatConversationDate(
                                                conversation.updated_at,
                                            )}
                                            className="shrink-0 text-right text-xs text-muted-foreground"
                                        >
                                            {formatConversationDate(
                                                conversation.updated_at,
                                            )}
                                        </time>
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Conversation area */}
            <div className="flex-1 overflow-y-auto">
                <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6">
                    {/* Welcome screen */}
                    {messages.length === 0 && (
                        <div className="my-auto flex min-h-[300px] flex-col items-center justify-center text-center">
                            <div className="mb-5 flex size-16 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                                <Sparkles className="size-8" />
                            </div>

                            <h2 className="text-2xl font-semibold tracking-tight">
                                Welcome to Lumen Health
                            </h2>

                            <p className="mt-3 max-w-md text-sm leading-6 text-muted-foreground">
                                How can I help you today? I can find a doctor,
                                check availability, and manage your visits.
                            </p>

                            <div className="mt-7 grid w-full max-w-xl gap-3 text-left sm:grid-cols-2">
                                <Suggestion
                                    icon={Search}
                                    label="Find a doctor"
                                    onClick={() =>
                                        void send('Help me find a doctor')
                                    }
                                />
                                <Suggestion
                                    icon={CalendarPlus}
                                    label="Book an appointment"
                                    onClick={() =>
                                        void send(
                                            'I would like to book an appointment',
                                        )
                                    }
                                />
                                <Suggestion
                                    icon={CalendarCheck}
                                    label="Check my appointments"
                                    onClick={() =>
                                        void send('Show me my appointments')
                                    }
                                />
                                <Suggestion
                                    icon={Stethoscope}
                                    label="Clinic information"
                                    onClick={() =>
                                        void send('Tell me about the clinic')
                                    }
                                />
                            </div>

                            <p className="mt-7 max-w-md text-xs leading-5 text-muted-foreground">
                                If you have severe or sudden chest discomfort or
                                other potentially urgent symptoms, seek
                                emergency medical care rather than waiting for
                                an appointment.
                            </p>
                        </div>
                    )}

                    {/* Chat messages */}
                    {messages.map((message) => {
                        const isUser = message.role === 'user';

                        return (
                            <div
                                key={message.id}
                                className={`flex w-full items-start gap-3 ${
                                    isUser ? 'justify-end' : 'justify-start'
                                }`}
                            >
                                {!isUser && (
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                        L
                                    </div>
                                )}

                                <div
                                    className={`max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-7 break-words whitespace-pre-wrap sm:max-w-[75%] ${
                                        isUser
                                            ? 'rounded-br-md bg-primary text-primary-foreground'
                                            : 'rounded-bl-md border bg-card text-card-foreground shadow-sm'
                                    }`}
                                >
                                    {message.content}
                                </div>

                                {isUser && (
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                                        You
                                    </div>
                                )}
                            </div>
                        );
                    })}

                    {/* Thinking indicator */}
                    {loading && (
                        <div
                            className="flex items-start gap-3"
                            role="status"
                            aria-live="polite"
                        >
                            <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                L
                            </div>

                            <div className="flex items-center gap-2 rounded-2xl rounded-bl-md border bg-card px-4 py-3 shadow-sm">
                                <span className="text-sm text-muted-foreground">
                                    Thinking
                                </span>

                                <span className="flex items-center gap-1">
                                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:-0.3s]" />
                                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:-0.15s]" />
                                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground" />
                                </span>
                            </div>
                        </div>
                    )}

                    <div ref={bottomRef} />
                </div>
            </div>

            {/* Message composer */}
            <div className="border-t bg-background/95 px-4 py-4 backdrop-blur sm:px-6">
                <div className="mx-auto w-full max-w-3xl">
                    <form
                        className="flex items-end gap-2 rounded-2xl border bg-card p-2 shadow-sm transition-shadow focus-within:ring-2 focus-within:ring-primary/20"
                        onSubmit={(event) => {
                            event.preventDefault();
                            void send(input);
                        }}
                    >
                        <textarea
                            value={input}
                            onChange={(event) => setInput(event.target.value)}
                            onKeyDown={(event) => {
                                if (
                                    event.key === 'Enter' &&
                                    !event.shiftKey &&
                                    !event.nativeEvent.isComposing
                                ) {
                                    event.preventDefault();
                                    void send(input);
                                }
                            }}
                            placeholder="Message your assistant..."
                            disabled={loading}
                            rows={1}
                            className="max-h-40 min-h-11 flex-1 resize-y bg-transparent px-3 py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                            aria-label="Message your assistant"
                        />

                        <Button
                            type="submit"
                            size="icon"
                            className="mb-0.5 size-10 shrink-0 rounded-xl"
                            disabled={loading || !input.trim()}
                            aria-label="Send message"
                        >
                            {loading ? (
                                <span className="size-4 animate-spin rounded-full border-2 border-current border-r-transparent" />
                            ) : (
                                <SendHorizontal className="size-4" />
                            )}
                        </Button>
                    </form>

                    <p className="mt-3 text-center text-xs text-muted-foreground">
                        AI can make mistakes. Please verify important medical
                        information.
                        <br />
                        Press Enter to send · Shift + Enter for a new line
                    </p>
                </div>
            </div>
        </div>
    );
}

function Suggestion({
    icon: Icon,
    label,
    onClick,
}: {
    icon: typeof Search;
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="group flex items-center gap-3 rounded-xl border border-border/70 bg-card p-3 text-sm transition hover:border-teal-300 hover:bg-teal-50/50"
        >
            <span className="flex size-8 items-center justify-center rounded-lg bg-muted text-muted-foreground group-hover:bg-teal-100 group-hover:text-teal-700">
                <Icon className="size-4" />
            </span>
            <span className="font-medium">{label}</span>
        </button>
    );
}
