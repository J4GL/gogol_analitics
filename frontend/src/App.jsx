import { useState, useEffect } from 'react';
import * as Tabs from '@radix-ui/react-tabs';
import TrafficTab from './components/TrafficTab';
import SettingsTab from './components/SettingsTab';

function App() {
  return (
    <div className="tabs-root">
      <h1 style={{ marginBottom: '20px', fontSize: '28px', color: '#333' }}>
        Gogol Analytics Dashboard
      </h1>
      
      <Tabs.Root defaultValue="traffic">
        <Tabs.List className="tabs-list">
          <Tabs.Trigger value="traffic" className="tabs-trigger">
            Traffic
          </Tabs.Trigger>
          <Tabs.Trigger value="settings" className="tabs-trigger">
            Settings
          </Tabs.Trigger>
        </Tabs.List>

        <Tabs.Content value="traffic" className="tabs-content">
          <TrafficTab />
        </Tabs.Content>

        <Tabs.Content value="settings" className="tabs-content">
          <SettingsTab />
        </Tabs.Content>
      </Tabs.Root>
    </div>
  );
}

export default App;
