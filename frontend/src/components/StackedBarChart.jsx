import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

function StackedBarChart({ data }) {
  const formatDate = (timestamp) => {
    const date = new Date(timestamp);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  };

  const chartData = data.map(item => ({
    name: formatDate(item.bucket_start),
    Bots: item.bots,
    'New Visitors': item.new_visitors,
    'Returning Visitors': item.returning_visitors
  }));

  return (
    <div className="chart-container">
      <h2 className="chart-title">Traffic Overview</h2>
      {chartData.length > 0 ? (
        <ResponsiveContainer width="100%" height={400}>
          <BarChart data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip />
            <Legend />
            <Bar dataKey="Bots" stackId="a" fill="#ef4444" />
            <Bar dataKey="New Visitors" stackId="a" fill="#22c55e" />
            <Bar dataKey="Returning Visitors" stackId="a" fill="#3b82f6" />
          </BarChart>
        </ResponsiveContainer>
      ) : (
        <div className="empty-state">
          <div className="empty-state-icon">📊</div>
          <div className="empty-state-text">No traffic data available yet</div>
        </div>
      )}
    </div>
  );
}

export default StackedBarChart;
