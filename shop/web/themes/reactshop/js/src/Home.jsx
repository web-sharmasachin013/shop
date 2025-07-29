import React from 'react';

const Home = () => {
  return (
    <div style={styles.container}>
      <h1 style={styles.heading}>Welcome to the Home Page</h1>
      <p style={styles.paragraph}>
        This is a simple React component. You can customize it as needed.
      </p>
    </div>
  );
};

const styles = {
  container: {
    textAlign: 'center',
    padding: '50px',
    backgroundColor: '#f0f4f8',
    borderRadius: '10px',
    marginTop: '40px',
    boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
  },
  heading: {
    color: '#333',
    fontSize: '2rem',
    marginBottom: '20px',
  },
  paragraph: {
    color: '#666',
    fontSize: '1.2rem',
  },
};

export default Home;
