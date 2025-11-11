import React from 'react'
import { createRoot } from 'react-dom/client'
import App from './App.jsx'

const mount = () => {
  const el = document.getElementById('glr-root')
  if (!el) return
  const root = createRoot(el)
  root.render(<App view={el.dataset.view || 'standings'} />)
}
mount()
