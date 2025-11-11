import React from 'react'
import { createRoot } from 'react-dom/client'
import App from './App.jsx'
const el = document.getElementById('glr-root')
if (el) createRoot(el).render(<App view={el.dataset.view || 'standings'} />)
