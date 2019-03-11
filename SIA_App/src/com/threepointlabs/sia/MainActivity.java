package com.threepointlabs.sia;

import java.io.IOException;
import java.io.InputStream;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.HashMap;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.client.HttpClient;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.protocol.BasicHttpContext;
import org.apache.http.protocol.HttpContext;

import android.os.AsyncTask;
import android.os.Bundle;
import android.app.Activity;
import android.content.Intent;
import android.speech.RecognizerIntent;
import android.speech.tts.TextToSpeech;
import android.speech.tts.TextToSpeech.OnUtteranceCompletedListener;
import android.speech.tts.UtteranceProgressListener;
import android.text.method.ScrollingMovementMethod;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ScrollView;
import android.widget.TextView;

public class MainActivity extends Activity {
	protected TextToSpeech TTSEngine;
	protected String ClientQuery;
	protected String ServerResponse;
	protected String LocalServer = "http://192.168.1.222/";
	//protected String RemoteServer = "http://24.127.202.190/";
	protected String RemoteServer = "http://casb1.cloudapp.net/1013/91911435fc21de251697499bc3014697/";
	protected String PublicServer = "http://3pointlabs.org/";
	
	protected String Prefix = "app/autodecide?r=";
	//protected String Prefix = "autodecide.php?r=";
	protected View ActivateButton;
	protected View ProgressIndicator;
	protected ViewGroup LogGroup;
	protected ScrollView SVContainer;
	protected int NumLines;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        ActivateButton = findViewById(R.id.button_activate);
        ProgressIndicator = findViewById(R.id.progressbar_indicator);
        LogGroup = (ViewGroup) findViewById(R.id.linearlayout_log);
        SVContainer = (ScrollView) findViewById(R.id.scrollview_container);
        NumLines = 0;
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        // Inflate Menu from XML
        getMenuInflater().inflate(R.menu.main, menu);
        return true;
    }
    
    public void onButtonPress(View view) {
    	activateSpeech(42);
    }
    public void activateSpeech(int requestCode) {
    	//Prepare TTS, takes a bit
    	TTSEngine = new TextToSpeech(this, null);
    	
    	showIndicator();
    	
    	//Create and Send Speech Recognition Intent
    	Intent RI = new Intent(RecognizerIntent.ACTION_RECOGNIZE_SPEECH);
    	RI.putExtra(RecognizerIntent.EXTRA_LANGUAGE_MODEL, RecognizerIntent.LANGUAGE_MODEL_FREE_FORM);
    	RI.putExtra(RecognizerIntent.EXTRA_PROMPT, "Please Speak Now");
    	startActivityForResult(RI, requestCode);
    }
    
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
    	super.onActivityResult(requestCode, resultCode, data);
    	if ((resultCode == RESULT_OK) && (data != null)) {
    		ArrayList<String> Results = data.getStringArrayListExtra(RecognizerIntent.EXTRA_RESULTS);
    		ClientQuery = Results.get(0);
    		addRow(ClientQuery);
    		Log.d("", ClientQuery);
    	}
    	ProcessSpeechTask PST = new ProcessSpeechTask();
    	PST.execute();
    }
    protected void hideIndicator() {
    	ProgressIndicator.setVisibility(View.GONE);
		ActivateButton.setVisibility(View.VISIBLE);
    }
    protected void showIndicator() {
    	ActivateButton.setVisibility(View.GONE);
    	ProgressIndicator.setVisibility(View.VISIBLE);
    }
    protected void addRow(String Value) {
    	ViewGroup newView = (ViewGroup) LayoutInflater.from(this).inflate(
                R.layout.log_item, LogGroup, false);
    	((TextView) newView.findViewById(R.id.logtextvalue)).setText(Value);
    	LogGroup.addView(newView, NumLines);
    	NumLines++;
    	SVContainer.scrollTo(0, LogGroup.getHeight());
    }

    private class ProcessSpeechTask extends AsyncTask<Void, Void, String> {
    	protected String doInBackground(Void... params) {
    		HttpClient LocalClient = new DefaultHttpClient();
    		HttpContext LocalContext = new BasicHttpContext();
    		try {
    			ClientQuery = URLEncoder.encode(ClientQuery, "UTF-8");
    		} 
    		catch(UnsupportedEncodingException e) {
    			e.printStackTrace();
    		}
    		HttpGet Get = new HttpGet(PublicServer + Prefix + ClientQuery);
    		try {
    			HttpResponse Response = LocalClient.execute(Get, LocalContext);
    			HttpEntity Entity = Response.getEntity();
    			ServerResponse = getASCIIContent(Entity);
    			Log.d("", ServerResponse);
    			
    			HashMap<String, String> TTSParam = new HashMap<String, String>();
    			//Utterance ID needed for Callback
    			TTSParam.put(TextToSpeech.Engine.KEY_PARAM_UTTERANCE_ID, "stringId");
    			
    			//Add View before Speak for Scroll
    			runOnUiThread(new Runnable() {
        			public void run() {
        				addRow(ServerResponse); //Views can only be altered by creator thread
        		    }
        		});
    			
    			TTSEngine.speak(ServerResponse, TextToSpeech.QUEUE_ADD, TTSParam);
    		}
    		catch (Exception e) {
    			return e.getLocalizedMessage();
    		}
    		
    		//Release TTS when done
    		TTSEngine.setOnUtteranceProgressListener(new TTSAutoShutdown());
    		return null;
    	}
    	/*
    	protected String getASCIIContent(HttpEntity Entity) throws IllegalStateException, IOException {
    		InputStream IS = Entity.getContent();
    		StringBuffer SB = new StringBuffer();
    		int N = 0;
    		do {
    			byte[] B = new byte[4096];
    			N = IS.read(B);
    			if (N != 0) {
    				SB.append(new String(B, 0, N));
    			}
    		} while (N != 0);
    		return SB.toString();
    	}
    	*/
    	protected String getASCIIContent(HttpEntity Entity) throws IllegalStateException, IOException {
    		InputStream IS = Entity.getContent();
    		StringBuffer SB = new StringBuffer();
    		int N = 0;
    		do {
    			byte[] B = new byte[4096];
    			N = IS.read(B);
    			if (N > 0) {
    				SB.append(new String(B, 0, N));
    			}
    		} while (N > 0);
    		return SB.toString();
    	}
    }
    protected class TTSAutoShutdown extends UtteranceProgressListener {

		@Override
		public void onDone(String arg0) {
			TTSEngine.shutdown();
			runOnUiThread(new Runnable() {
    			public void run() {
    				hideIndicator(); //Views can only be altered by creator thread
    				SVContainer.scrollTo(0, LogGroup.getHeight());
    		    }
    		});
		}

		@Override
		public void onError(String arg0) {
			// TODO Auto-generated method stub
			
		}

		@Override
		public void onStart(String arg0) {
			// TODO Auto-generated method stub
			
		}
    }
}
