function App() {
  return (
    <div className="flex min-h-screen flex-col bg-slate-50 text-slate-900">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
          <a href="/" className="text-lg font-semibold text-green-700">
            Positive Tree NGO
          </a>
          <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
            Backend app
          </span>
        </div>
      </header>

      <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col items-center justify-center px-6 py-16 text-center">
        <p className="mb-3 text-sm font-semibold uppercase tracking-wide text-green-600">
          React foundation
        </p>
        <h1 className="mb-4 text-3xl font-bold tracking-tight sm:text-4xl">App shell ready</h1>
        <p className="max-w-xl text-slate-600">
          Blank starter for the backend team. Build features in <code className="rounded bg-slate-200 px-1.5 py-0.5 text-sm">app/src/</code>.
        </p>
      </main>
    </div>
  )
}

export default App
